<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShipmentRepositoryInterface
{
    public function find(int $id): object;
    public function findForUser(int $userId, int $id): object;
    public function findByIdempotencyKey(string $key): ?object;
    public function findByProviderReference(string $reference): ?object;
    public function listForUserOrder(int $userId, int $orderId): iterable;
    public function create(array $attributes): object;
    public function updateProviderData(object $shipment, array $data): object;
    public function updateProviderStatus(object $shipment, string $status, ?string $note = null): object;
    public function updateStatus(object $shipment, string $status, ?int $actorId, ?string $note = null): object;
}
