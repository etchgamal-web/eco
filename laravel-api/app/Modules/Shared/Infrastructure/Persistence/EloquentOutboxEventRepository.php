<?php

namespace App\Modules\Shared\Infrastructure\Persistence;

use App\Models\OutboxEvent;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class EloquentOutboxEventRepository implements OutboxEventRepositoryInterface
{
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

    public function claim(int $eventId, int $staleAfterMinutes = 10): bool
    {
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

    public function markFailed(string $deduplicationKey, string $error): void
    {
        OutboxEvent::query()->where('deduplication_key', $deduplicationKey)->update([
            'status' => 'pending',
            'attempt_count' => \Illuminate\Database\Query\Expression::raw('attempt_count + 1'),
            'last_error' => $error,
            'next_attempt_at' => now()->addMinutes(5),
        ]);
    }
}
