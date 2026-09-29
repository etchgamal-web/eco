<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Infrastructure\Models\ShipmentOperation;
use Illuminate\Database\Eloquent\Builder;

final class EloquentShipmentOperationRepository implements ShipmentOperationRepositoryInterface
{
    public function start(int $shipmentId, string $operation, string $idempotencyKey): void
    {
        $record = ShipmentOperation::query()->firstOrNew(['shipment_id' => $shipmentId, 'operation' => $operation, 'idempotency_key' => $idempotencyKey]);
        if ($record->exists && in_array($record->status, ['provider_created', 'confirmed', 'failed', 'ambiguous'], true)) return;
        $record->status = 'processing';
        $record->attempt_count = ((int) $record->attempt_count) + 1;
        $record->next_retry_at = null;
        $record->save();
    }

    public function acquireLease(int $shipmentId, string $operation, string $token, int $seconds = 300): bool
    {
        $now = now();
        return ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)
            ->where(function (Builder $query) use ($token, $now): void {
                $query->whereNull('lease_token')->orWhere('lease_expires_at', '<=', $now)->orWhere('lease_token', $token);
            })->update(['lease_token' => $token, 'lease_expires_at' => $now->addSeconds($seconds)]) === 1;
    }

    public function releaseLease(int $shipmentId, string $operation, string $token): void
    {
        ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->where('lease_token', $token)->update(['lease_token' => null, 'lease_expires_at' => null]);
    }

    public function ownsLease(int $shipmentId, string $operation, string $token): bool
    {
        return ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->where('lease_token', $token)->where('lease_expires_at', '>', now())->exists();
    }

    public function successfulResponse(int $shipmentId, string $operation): ?array
    {
        $record = ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->first();
        if ($record?->status !== 'provider_created') return null;
        return (array) $record->response_payload;
    }

    public function hasAttempted(int $shipmentId, string $operation): bool
    {
        return ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation)->where('attempt_count', '>', 0)->exists();
    }

    public function complete(int $shipmentId, string $operation, string $status, ?string $providerReference, array $response, ?string $leaseToken = null): bool
    {
        $query = ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation);
        if ($leaseToken !== null) $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        return $query->update(['status' => $status, 'provider_reference' => $providerReference, 'response_payload' => $response, 'last_error' => null, 'next_retry_at' => null, 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }

    public function fail(int $shipmentId, string $operation, string $error, ?string $leaseToken = null): bool
    {
        $query = ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation);
        if ($leaseToken !== null) $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        return $query->update(['status' => 'processing', 'last_error' => $error, 'next_retry_at' => now()->addMinutes(5), 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }

    public function failAmbiguous(int $shipmentId, string $operation, string $error, ?string $leaseToken = null): bool
    {
        $query = ShipmentOperation::query()->where('shipment_id', $shipmentId)->where('operation', $operation);
        if ($leaseToken !== null) $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        return $query->update(['status' => 'ambiguous', 'last_error' => $error, 'next_retry_at' => null, 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }
}
