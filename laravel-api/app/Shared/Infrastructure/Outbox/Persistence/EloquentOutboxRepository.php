<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox\Persistence;

use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentOutboxRepository implements OutboxRepositoryInterface
{
    public function find(int $eventId): ?OutboxEvent
    {
        return OutboxEvent::query()->find($eventId);
    }

    public function findByDeduplicationKey(string $key): ?OutboxEvent
    {
        return OutboxEvent::query()->where('deduplication_key', $key)->first();
    }

    public function add(OutboxMessage $message): OutboxEvent
    {
        try {
            return OutboxEvent::query()->firstOrCreate(
                ['deduplication_key' => $message->key()],
                [
                    'aggregate_type' => $message->aggregateType,
                    'aggregate_id' => $message->aggregateId,
                    'event_type' => $message->eventType,
                    'status' => 'pending',
                    'payload' => $message->payload,
                    'next_attempt_at' => $message->availableAt,
                ],
            );
        } catch (QueryException $exception) {
            $existing = $this->findByDeduplicationKey($message->key());
            if ($existing !== null) return $existing;
            throw $exception;
        }
    }

    public function claim(int $limit): array
    {
        $limit = max(1, $limit);
        $leaseUntil = now()->addMinutes((int) config('outbox.lease_minutes', 5));
        $events = [];

        OutboxEvent::query()
            ->where(function (Builder $query): void {
                $query->where(function (Builder $pending): void {
                    $pending->where('status', 'pending')
                        ->where(function (Builder $available): void {
                            $available->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                        });
                })->orWhere(function (Builder $processing): void {
                    $processing->where('status', 'processing')->where('lease_until', '<=', now());
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->each(function (int $id) use (&$events, $leaseUntil): void {
                $claimToken = (string) Str::uuid();
                $claimed = OutboxEvent::query()
                    ->whereKey($id)
                    ->where(function (Builder $query): void {
                        $query->where(function (Builder $pending): void {
                            $pending->where('status', 'pending')
                                ->where(function (Builder $available): void {
                                    $available->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                                });
                        })->orWhere(function (Builder $processing): void {
                            $processing->where('status', 'processing')->where('lease_until', '<=', now());
                        });
                    })
                    ->update([
                        'status' => 'processing',
                        'claim_token' => $claimToken,
                        'lease_until' => $leaseUntil,
                        'updated_at' => now(),
                    ]);

                if ($claimed === 1) {
                    $event = $this->find($id);
                    if ($event !== null) {
                        $events[] = $event;
                    }
                }
            });

        return $events;
    }

    public function countByStatus(): array
    {
        return OutboxEvent::query()->select('status')->selectRaw('count(*) as count')->groupBy('status')->pluck('count', 'status')->map(static fn ($count): int => (int) $count)->all();
    }

    public function ownsClaim(int $eventId, string $claimToken): bool
    {
        return OutboxEvent::query()
            ->whereKey($eventId)
            ->where('status', 'processing')
            ->where('claim_token', $claimToken)
            ->exists();
    }

    public function markProcessed(int $eventId, string $claimToken): bool
    {
        return OutboxEvent::query()
            ->whereKey($eventId)
            ->where('status', 'processing')
            ->where('claim_token', $claimToken)
            ->update([
                'status' => 'dispatched',
                'dispatched_at' => now(),
                'lease_until' => null,
                'claim_token' => null,
                'last_error' => null,
            ]) === 1;
    }

    public function markFailed(int $eventId, string $claimToken, string $error): bool
    {
        return DB::transaction(function () use ($eventId, $claimToken, $error): bool {
            $event = OutboxEvent::query()
                ->lockForUpdate()
                ->whereKey($eventId)
                ->where('status', 'processing')
                ->where('claim_token', $claimToken)
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
                'lease_until' => null,
                'claim_token' => null,
                'next_attempt_at' => $exhausted ? null : now()->addMinutes((int) config('outbox.retry_delay_minutes', 5)),
            ]);

            return $exhausted;
        });
    }

    public function markAmbiguous(int $eventId, string $claimToken, string $error): bool
    {
        return OutboxEvent::query()
            ->whereKey($eventId)
            ->where('status', 'processing')
            ->where('claim_token', $claimToken)
            ->update([
                'status' => 'ambiguous',
                'last_error' => $error,
                'lease_until' => null,
                'claim_token' => null,
                'next_attempt_at' => null,
            ]) === 1;
    }
}
