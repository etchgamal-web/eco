<?php

namespace App\Modules\Shared\Domain\Contracts;

interface OutboxEventRepositoryInterface
{
    public function find(int $eventId): ?object;

    public function record(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        string $deduplicationKey,
        array $payload = [],
    ): object;

    /** Atomically reserves an event for one dispatcher/worker. */
    public function claim(int $eventId, int $staleAfterMinutes = 0): bool;

    public function markDispatched(string $deduplicationKey): void;

    public function markFailed(string $deduplicationKey, string $error): bool;
}
