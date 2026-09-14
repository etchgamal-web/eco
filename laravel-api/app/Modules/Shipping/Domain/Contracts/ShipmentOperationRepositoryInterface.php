<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShipmentOperationRepositoryInterface
{
    public function start(int $shipmentId, string $operation, string $idempotencyKey): void;

    public function successfulResponse(int $shipmentId, string $operation): ?array;

    public function complete(int $shipmentId, string $operation, string $status, ?string $providerReference, array $response): void;

    public function fail(int $shipmentId, string $operation, string $error): void;
}
