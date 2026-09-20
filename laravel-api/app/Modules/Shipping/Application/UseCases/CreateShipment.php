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
        if (! in_array((string) $order->status, ['confirmed', 'processing'], true)) {
            throw new ShippingException('Shipment can only be created for a confirmed or processing order.');
        }

        $method = $this->methods->find($data->shippingMethodId);
        if (! $method->is_active) throw new ShippingException('Shipping method is inactive.');
        if ($method->currency !== $order->currency) throw new ShippingException('Shipping currency does not match the order.');

        $existing = $this->shipments->findByIdempotencyKey($data->idempotencyKey);
        if ($existing !== null) {
            if ($existing->order_id !== $order->id) throw new ShippingException('Idempotency key belongs to another order.');
            return $existing;
        }

        return $this->shipments->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'shipping_method_id' => $method->id,
            'method_code' => $method->code,
            'fee' => $this->rates->calculate($order, $method),
            'currency' => $order->currency,
            'status' => 'pending',
            'address_snapshot' => $order->shipping_address,
            'idempotency_key' => $data->idempotencyKey,
            'metadata' => ['carrier' => $method->carrier],
        ]);
    }
}
