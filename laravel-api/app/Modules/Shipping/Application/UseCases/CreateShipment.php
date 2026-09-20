<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingRateCalculatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;

final class CreateShipment
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ShippingMethodRepositoryInterface $methods,
        private readonly ShippingRateCalculatorInterface $rates,
        private readonly ShipmentRepositoryInterface $shipments,
    ) {
    }

    public function execute(int $orderId, CreateShipmentData $data): object
    {
        $order = $this->orders->find($orderId);
        if ((string) $order->status !== 'processing') {
            throw new ShippingException('Shipment can only be created for an order in processing status.');
        }

        $method = $this->methods->find($data->shippingMethodId);
        if (! $method->is_active) throw new ShippingException('Shipping method is inactive.');
        if ($method->currency !== $order->currency) throw new ShippingException('Shipping currency does not match the order.');
        $providerCode = strtolower(trim($data->providerCode));
        if ($providerCode === '') throw new ShippingException('A shipping provider must be selected.');
        $methodProvider = strtolower(trim((string) ($method->carrier ?? '')));
        if ($methodProvider !== '' && $methodProvider !== $providerCode) {
            throw new ShippingException('The selected provider is not available for this shipping method.');
        }

        $existing = $this->shipments->findByIdempotencyKey($data->idempotencyKey);
        if ($existing !== null) {
            if ($existing->order_id !== $order->id) throw new ShippingException('Idempotency key belongs to another order.');
            return $existing;
        }

        return $this->shipments->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'shipping_method_id' => $method->id,
            'provider_code' => $providerCode,
            'method_code' => $method->code,
            'fee' => $this->rates->calculate($order, $method),
            'currency' => $order->currency,
            'status' => 'pending',
            'creation_status' => 'creation_pending',
            'address_snapshot' => $order->shipping_address,
            'idempotency_key' => $data->idempotencyKey,
            'metadata' => ['carrier' => $method->carrier, 'provider' => $providerCode],
        ]);
    }
}
