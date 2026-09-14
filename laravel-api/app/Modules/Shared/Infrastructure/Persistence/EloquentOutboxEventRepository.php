<?php

namespace App\Modules\Shared\Infrastructure\Persistence;

use App\Models\OutboxEvent;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class EloquentOutboxEventRepository implements OutboxEventRepositoryInterface
{
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
