<?php

namespace Tests\Unit;

use App\Shared\Infrastructure\Outbox\Processing\ProcessOutboxEvent;
use App\Shared\Infrastructure\Outbox\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
use App\Shared\Infrastructure\Outbox\Persistence\EloquentOutboxRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OutboxPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_records_an_event_idempotently(): void
    {
        $repository = app(OutboxRepositoryInterface::class);

        $message = new OutboxMessage('payment.create.requested', 'payment', 7, ['payment_id' => 7], deduplicationKey: 'payment:create:test-7');
        $first = $repository->add($message);
        $second = $repository->add($message);

        $this->assertInstanceOf(EloquentOutboxRepository::class, $repository);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('outbox_events', 1);
        $this->assertDatabaseHas('outbox_events', [
            'deduplication_key' => 'payment:create:test-7',
            'status' => 'pending',
        ]);
    }

    public function test_claim_is_atomic_and_only_the_first_worker_wins(): void
    {
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment',
            'aggregate_id' => 7,
            'event_type' => 'payment.create.requested',
            'deduplication_key' => 'payment:create:claim-test',
            'status' => 'pending',
            'payload' => ['payment_id' => 7],
        ]);
        $repository = app(OutboxRepositoryInterface::class);

        $claimed = $repository->claim(10);
        $this->assertCount(1, $claimed);
        $this->assertSame($event->id, $claimed[0]->id);
        $this->assertCount(0, $repository->claim(10));
        $this->assertDatabaseHas('outbox_events', ['id' => $event->id, 'status' => 'processing']);
    }

    public function test_failed_event_retries_through_outbox_and_then_exhausts(): void
    {
        config(['outbox.max_attempts' => 2, 'outbox.retry_delay_minutes' => 5]);
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment',
            'aggregate_id' => 9,
            'event_type' => 'payment.create.requested',
            'deduplication_key' => 'payment:create:retry-test',
            'status' => 'processing',
            'payload' => ['payment_id' => 9],
        ]);
        $repository = app(OutboxRepositoryInterface::class);

        self::assertFalse($repository->markFailed((int) $event->id, 'temporary failure'));
        $event->refresh();
        self::assertSame('pending', $event->status);
        self::assertSame(1, $event->attempt_count);
        self::assertNotNull($event->next_attempt_at);

        self::assertTrue($repository->markFailed((int) $event->id, 'final failure'));
        $event->refresh();
        self::assertSame('failed', $event->status);
        self::assertSame(2, $event->attempt_count);
        self::assertNull($event->next_attempt_at);
    }

    public function test_queue_failure_returns_event_to_outbox_retry_cycle(): void
    {
        config(['outbox.max_attempts' => 2, 'outbox.retry_delay_minutes' => 5]);
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment',
            'aggregate_id' => 10,
            'event_type' => 'payment.create.requested',
            'deduplication_key' => 'payment:create:timeout-test',
            'status' => 'processing',
            'payload' => ['payment_id' => 10],
        ]);

        (new ProcessOutboxEvent((int) $event->id))->failed(new \RuntimeException('worker timeout'));

        $event->refresh();
        self::assertSame('pending', $event->status);
        self::assertSame(1, $event->attempt_count);
        self::assertSame('worker timeout', $event->last_error);
    }
}
