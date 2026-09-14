<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;

final class ReconcileShipment
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShippingProviderInterface $providers,
    ) {
    }

    public function execute(int $shipmentId): object
    {
        $shipment = $this->shipments->find($shipmentId);
        if (! in_array($shipment->status, ['pending', 'processing', 'provider_created', 'picked_up', 'in_transit', 'out_for_delivery'], true)) {
            return $shipment;
        }
        if (! $this->providers->supports($shipment)) {
            return $shipment;
        }
        $response = $this->providers->track($shipment);
        $state = data_get($response, 'data.0.state.code', data_get($response, 'data.state.code'));
        $status = match ((int) $state) {
            45 => 'delivered',
            41 => 'out_for_delivery',
            30, 24 => 'in_transit',
            21, 23 => 'picked_up',
            48, 49, 100, 101 => 'cancelled',
            10, 20 => 'provider_created',
            default => $shipment->status,
        };
        if ($status === $shipment->status) {
            return $shipment;
        }
        return $this->shipments->updateProviderStatus($shipment, $status, 'Carrier reconciliation state ' . (string) $state);
    }
}
