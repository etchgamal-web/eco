<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;
use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingRateCalculatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class CreateShipment
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly ShippingMethodRepositoryInterface $methods,
        private readonly ShippingRateCalculatorInterface $rates,
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShippingProviderInterface $providers,
        private readonly ShipmentOperationRepositoryInterface $operations,
        private readonly OutboxEventRepositoryInterface $outbox,
    ) {}

    public function execute(int $orderId, CreateShipmentData $data): object
    {
        $user = $this->authentication->user();
        if ($user === null) throw new AuthenticationException('Unauthenticated.');
        $order = $this->orders->findForUser($user->id, $orderId);
        $method = $this->methods->find($data->shippingMethodId);
        if (!$method->is_active) throw new ShippingException('Shipping method is inactive.');
        if ($method->currency !== $order->currency) throw new ShippingException('Shipping currency does not match the order.');
        $existing = $this->shipments->findByIdempotencyKey($data->idempotencyKey);
        if ($existing !== null) {
            if ($existing->order_id !== $order->id) throw new ShippingException('Idempotency key belongs to another order.');
            return $this->dispatchIfNeeded($existing);
        }
        $fee = $this->rates->calculate($order, $method);
        $shipment = $this->shipments->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'shipping_method_id' => $method->id,
            'method_code' => $method->code,
            'fee' => $fee,
            'currency' => $order->currency,
            'status' => 'pending',
            'address_snapshot' => $order->shipping_address,
            'idempotency_key' => $data->idempotencyKey,
            'metadata' => ['carrier' => $method->carrier],
        ]);
        return $this->dispatchIfNeeded($shipment);
    }

    private function dispatchIfNeeded(object $shipment): object
    {
        if (! $this->providers->supports($shipment) || data_get($shipment->metadata, 'provider_reference')) {
            return $shipment;
        }
        $previousResult = $this->operations->successfulResponse((int) $shipment->id, 'create');
        if ($previousResult !== null) {
            return $this->shipments->updateProviderData($shipment, $previousResult);
        }
        $this->operations->start((int) $shipment->id, 'create', $shipment->idempotency_key);
        try {
            $result = $this->providers->create($shipment);
            $this->operations->complete((int) $shipment->id, 'create', ($result['tracking_number'] ?? null) !== null ? 'provider_created' : 'pending', data_get($result, 'metadata.provider_reference'), $result);
            $this->outbox->markDispatched('shipment:create:' . $shipment->idempotency_key);
        } catch (\Throwable $exception) {
            $this->operations->fail((int) $shipment->id, 'create', $exception->getMessage());
            $this->outbox->markFailed('shipment:create:' . $shipment->idempotency_key, $exception->getMessage());
            throw $exception;
        }
        return $this->shipments->updateProviderData($shipment, $result);
    }
}
