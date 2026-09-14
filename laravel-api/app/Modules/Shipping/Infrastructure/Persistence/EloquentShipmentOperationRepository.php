<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Models\ShipmentOperation;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;

final class EloquentShipmentOperationRepository implements ShipmentOperationRepositoryInterface
{
    public function start(int $shipmentId, string $operation, string $idempotencyKey): void
    {
        $record = ShipmentOperation::query()->firstOrNew([
            'shipment_id' => $shipmentId,
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
        ]);
        $record->status = 'processing';
        $record->attempt_count = ((int) $record->attempt_count) + 1;
        $record->next_retry_at = null;
        $record->save();
    }

    public function successfulResponse(int $shipmentId, string $operation): ?array
    {
        $record = ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->first();
        if ($record?->status !== 'provider_created') {
            return null;
        }
        return (array) $record->response_payload;
    }

    public function complete(int $shipmentId, string $operation, string $status, ?string $providerReference, array $response): void
    {
        ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->update([
            'status' => $status,
            'provider_reference' => $providerReference,
            'response_payload' => $response,
            'last_error' => null,
            'next_retry_at' => null,
        ]);
    }

    public function fail(int $shipmentId, string $operation, string $error): void
    {
        ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->update([
            'status' => 'processing',
            'last_error' => $error,
            'next_retry_at' => now()->addMinutes(5),
        ]);
    }
}
