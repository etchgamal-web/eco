<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentPricingSnapshotRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingPricingCalculatorInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;

final class CreateShipment
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly ShippingMethodRepositoryInterface $methods,
        private readonly ShippingPricingCalculatorInterface $rates,
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentPricingSnapshotRepositoryInterface $snapshots,
    ) {}

    public function execute(int $orderId, CreateShipmentData $data): object
    {
        return $this->transactions->run(function () use ($orderId, $data): object {
            $order = $this->orders->find($orderId);
            if ((string) $order->status !== 'processing') {
                throw new ShippingException('Shipment can only be created for an order in processing status.');
            }

            $method = $this->methods->find($data->shippingMethodId);
            if (! $method->is_active) {
                throw new ShippingException('Shipping method is inactive.');
            }
            if ($method->currency !== $order->currency) {
                throw new ShippingException('Shipping currency does not match the order.');
            }
            $providerCode = strtolower(trim($data->providerCode));
            if ($providerCode === '') {
                throw new ShippingException('A shipping provider must be selected.');
            }
            $methodProvider = strtolower(trim((string) ($method->carrier ?? '')));
            if ($methodProvider !== '' && $methodProvider !== $providerCode) {
                throw new ShippingException('The selected provider is not available for this shipping method.');
            }

            $existing = $this->shipments->findByIdempotencyKey($data->idempotencyKey);
            if ($existing !== null) {
                if ($existing->order_id !== $order->id) {
                    throw new ShippingException('Idempotency key belongs to another order.');
                }

                return $existing;
            }

            $breakdown = $this->rates->calculateBreakdown($order, $method, $providerCode);
            $zoneCode = $breakdown->zoneCode;
            $shipment = $this->shipments->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'shipping_method_id' => $method->id,
                'provider_code' => $providerCode,
                'method_code' => $method->code,
                'weight' => $breakdown->weight,
                'item_quantity' => $breakdown->quantity,
                'zone_code' => $zoneCode,
                'fee' => $breakdown->total,
                'currency' => $order->currency,
                'status' => 'pending',
                'creation_status' => 'creation_pending',
                'address_snapshot' => $order->shipping_address,
                'idempotency_key' => $data->idempotencyKey,
                'metadata' => ['carrier' => $method->carrier, 'provider' => $providerCode],
            ]);
            $this->orders->setShippingCost($order->id, $breakdown->total);
            $this->snapshots->create([
                'shipment_id' => $shipment->id,
                'shipping_provider_id' => $breakdown->provider?->id,
                'shipping_pricing_plan_id' => $breakdown->plan?->id,
                'pricing_method' => $breakdown->pricingMethod,
                'weight' => $breakdown->weight,
                'item_quantity' => $breakdown->quantity,
                'zone_code' => $zoneCode,
                'currency' => $order->currency,
                'base_amount' => $breakdown->total - array_sum(array_column($breakdown->fees, 'applied')),
                'applied_fees' => $breakdown->fees,
                'total_expected_cost' => $breakdown->total,
                'calculation_inputs' => ['weight' => $breakdown->weight, 'quantity' => $breakdown->quantity, 'zone_code' => $zoneCode, 'provider_code' => $providerCode],
            ]);

            return $shipment;
        });
    }
}
