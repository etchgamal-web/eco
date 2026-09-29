<?php

namespace Tests\Unit;

use App\Shared\Infrastructure\Outbox\Processing\ProcessOutboxEvent;
use App\Shared\Infrastructure\Outbox\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
use App\Shared\Infrastructure\Outbox\Persistence\EloquentOutboxRepository;
use App\Shared\Infrastructure\Outbox\Processing\OutboxProcessor;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use App\Modules\Payment\Infrastructure\Persistence\EloquentPaymentOperationRepository;
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

    public function test_stale_worker_cannot_complete_or_fail_a_newer_claim(): void
    {
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment',
            'aggregate_id' => 11,
            'event_type' => 'payment.create.requested',
            'deduplication_key' => 'payment:create:stale-worker',
            'status' => 'pending',
            'payload' => ['payment_id' => 11],
        ]);
        $repository = app(OutboxRepositoryInterface::class);
        $firstClaim = $repository->claim(1);
        self::assertSame('processing', $firstClaim[0]->status);
        $oldToken = (string) $firstClaim[0]->claim_token;

        $event->update(['lease_until' => now()->subSecond()]);
        $secondClaim = $repository->claim(1);
        $newToken = (string) $secondClaim[0]->claim_token;
        self::assertNotSame($oldToken, $newToken);

        self::assertFalse($repository->markProcessed((int) $event->id, $oldToken));
        self::assertFalse($repository->markFailed((int) $event->id, $oldToken, 'stale worker'));
        $event->refresh();
        self::assertSame('processing', $event->status);
        self::assertSame($newToken, $event->claim_token);
        self::assertTrue($repository->markProcessed((int) $event->id, $newToken));
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
            'claim_token' => 'token-retry',
            'payload' => ['payment_id' => 9],
        ]);
        $repository = app(OutboxRepositoryInterface::class);

        self::assertFalse($repository->markFailed((int) $event->id, 'token-retry', 'temporary failure'));
        $event->refresh();
        self::assertSame('pending', $event->status);
        self::assertSame(1, $event->attempt_count);
        self::assertNotNull($event->next_attempt_at);

        $event->update(['next_attempt_at' => now()->subSecond()]);
        $secondClaim = $repository->claim(1);
        self::assertCount(1, $secondClaim);
        self::assertTrue($repository->markFailed((int) $event->id, (string) $secondClaim[0]->claim_token, 'final failure'));
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
            'claim_token' => 'token-retry',
            'payload' => ['payment_id' => 10],
        ]);

        (new ProcessOutboxEvent((int) $event->id, 'token-retry'))->failed(new \RuntimeException('worker timeout'));

        $event->refresh();
        self::assertSame('pending', $event->status);
        self::assertSame(1, $event->attempt_count);
        self::assertSame('worker timeout', $event->last_error);
    }

    public function test_unsupported_event_is_not_marked_as_dispatched(): void
    {
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment',
            'aggregate_id' => 12,
            'event_type' => 'payment.typoed.event',
            'deduplication_key' => 'payment:unsupported:12',
            'status' => 'processing',
            'claim_token' => 'unsupported-token',
            'payload' => [],
        ]);

        app(OutboxProcessor::class)->dispatch((int) $event->id, 'unsupported-token');

        $event->refresh();
        self::assertNotSame('dispatched', $event->status);
        self::assertStringContainsString('Unsupported outbox event type', (string) $event->last_error);
    }

    public function test_stale_payment_operation_cannot_complete_after_new_lease(): void
    {
        $user = \App\Modules\Auth\Infrastructure\Models\User::factory()->create();
        $order = \App\Modules\Order\Infrastructure\Models\CustomerOrder::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100,
            'currency' => 'EGP',
        ]);
        $payment = \App\Modules\Payment\Infrastructure\Models\Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'method' => 'cash_on_delivery',
            'amount' => 100,
            'currency' => 'EGP',
            'status' => 'processing',
            'idempotency_key' => 'operation-fence-test',
        ]);
        PaymentOperation::query()->create([
            'payment_id' => $payment->id,
            'operation' => 'create',
            'status' => 'processing',
            'idempotency_key' => 'operation-fence-test',
            'attempt_count' => 1,
        ]);
        $operations = app(EloquentPaymentOperationRepository::class);
        self::assertTrue($operations->acquireLease((int) $payment->id, 'create', 'payment-old', 1));
        PaymentOperation::query()->where('payment_id', $payment->id)->update(['lease_expires_at' => now()->subSecond()]);
        self::assertTrue($operations->acquireLease((int) $payment->id, 'create', 'payment-new', 300));
        self::assertFalse($operations->complete((int) $payment->id, 'create', 'confirmed', 'old-ref', [], 'payment-old'));
        self::assertTrue($operations->complete((int) $payment->id, 'create', 'confirmed', 'new-ref', [], 'payment-new'));
    }
}
