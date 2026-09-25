<?php

namespace App\Modules\Shipping\Infrastructure\Providers;

use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;

final class ManualShippingProvider implements ShippingProviderInterface
{
    public function supports(object $shipment): bool
    {
        return strtolower((string) ($shipment->provider_code ?? '')) === 'manual';
    }

    public function create(object $shipment): array
    {
        $tracking = (string) data_get($shipment->metadata, 'manual_tracking_number', 'MAN-'.$shipment->id);

        return ['tracking_number' => $tracking, 'metadata' => ['provider_reference' => $tracking, 'manual' => true]];
    }

    public function recover(object $shipment): ?array
    {
        return $this->create($shipment);
    }

    public function track(object $shipment): array
    {
        return ['status' => $shipment->status, 'metadata' => ['manual' => true]];
    }

    public function cancel(object $shipment): array
    {
        return ['status' => 'cancelled', 'metadata' => ['manual' => true]];
    }
}
