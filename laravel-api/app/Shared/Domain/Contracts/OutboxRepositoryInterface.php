<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

use App\Shared\Domain\Data\OutboxMessage;

interface OutboxRepositoryInterface
{
    public function find(int $eventId): ?object;
    public function findByDeduplicationKey(string $key): ?object;
    public function add(OutboxMessage $message): object;
    /** @return list<object> */
    public function claim(int $limit): array;
    /** @return array<string, int> */
    public function countByStatus(): array;
    public function markProcessed(int $eventId): void;
    public function markFailed(int $eventId, string $error): bool;
}
