<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShipmentOperationRepositoryInterface
{
    public function start(int $shipmentId, string $operation, string $idempotencyKey): void;

    public function acquireLease(int $shipmentId, string $operation, string $token, int $seconds = 300): bool;

    public function releaseLease(int $shipmentId, string $operation, string $token): void;

    public function ownsLease(int $shipmentId, string $operation, string $token): bool;

    public function successfulResponse(int $shipmentId, string $operation): ?array;

    public function hasAttempted(int $shipmentId, string $operation): bool;

    public function complete(int $shipmentId, string $operation, string $status, ?string $providerReference, array $response, ?string $leaseToken = null): bool;

    public function fail(int $shipmentId, string $operation, string $error, ?string $leaseToken = null): bool;

    public function failAmbiguous(int $shipmentId, string $operation, string $error, ?string $leaseToken = null): bool;
}
