<?php

namespace App\Modules\Shared\Domain\Contracts;

use App\Models\OutboxEvent;

interface OutboxEventRepositoryInterface
{
    public function record(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        string $deduplicationKey,
        array $payload = [],
    ): OutboxEvent;

    /** Atomically reserves an event for one dispatcher/worker. */
    public function claim(int $eventId, int $staleAfterMinutes = 10): bool;

    public function markDispatched(string $deduplicationKey): void;

    public function markFailed(string $deduplicationKey, string $error): void;
}
