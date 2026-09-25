<?php

namespace App\Modules\Shared\Infrastructure\Persistence;

use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Shared\Infrastructure\Models\OutboxEvent;

final class EloquentOutboxEventRepository implements OutboxEventRepositoryInterface
{
    public function find(int $eventId): ?OutboxEvent
    {
        return OutboxEvent::query()->find($eventId);
    }

    public function record(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        string $deduplicationKey,
        array $payload = [],
    ): OutboxEvent {
        return OutboxEvent::query()->firstOrCreate(
            ['deduplication_key' => $deduplicationKey],
            [
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_type' => $eventType,
                'status' => 'pending',
                'payload' => $payload,
            ],
        );
    }

    public function claim(int $eventId, int $staleAfterMinutes = 0): bool
    {
        $staleAfterMinutes = $staleAfterMinutes > 0 ? $staleAfterMinutes : (int) config('outbox.lease_minutes', 5);

        return OutboxEvent::query()
            ->whereKey($eventId)
            ->where(function ($query) use ($staleAfterMinutes): void {
                $query->where('status', 'pending')
                    ->where(function ($pending): void {
                        $pending->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                    })
                    ->orWhere(function ($processing) use ($staleAfterMinutes): void {
                        $processing->where('status', 'processing')
                            ->where('updated_at', '<=', now()->subMinutes($staleAfterMinutes));
                    });
            })
            ->update(['status' => 'processing', 'updated_at' => now()]) === 1;
    }

    public function markDispatched(string $deduplicationKey): void
    {
        OutboxEvent::query()->where('deduplication_key', $deduplicationKey)->update([
            'status' => 'dispatched',
            'dispatched_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markFailed(string $deduplicationKey, string $error): bool
    {
        $event = OutboxEvent::query()
            ->where('deduplication_key', $deduplicationKey)
            ->whereIn('status', ['pending', 'processing'])
            ->first();
        if ($event === null) {
            return false;
        }

        $attempts = (int) $event->attempt_count + 1;
        $exhausted = $attempts >= (int) config('outbox.max_attempts', 5);
        $event->update([
            'status' => $exhausted ? 'failed' : 'pending',
            'attempt_count' => $attempts,
            'last_error' => $error,
            'next_attempt_at' => $exhausted ? null : now()->addMinutes((int) config('outbox.retry_delay_minutes', 5)),
        ]);

        return $exhausted;
    }
}
