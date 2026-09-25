<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;

final class GetPublicShipmentTracking
{
    public function __construct(private readonly ShipmentRepositoryInterface $shipments) {}

    public function execute(string $token): ?array
    {
        $shipment = $this->shipments->findByPublicTrackingToken($token);
        if ($shipment === null) return null;
        return [
            'status' => $shipment->status,
            'tracking_number' => $shipment->tracking_number,
            'provider_code' => $shipment->provider_code,
            'events' => $shipment->events->map(static fn (object $event): array => [
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'note' => $event->note,
                'created_at' => $event->created_at,
            ])->values()->all(),
        ];
    }
}
