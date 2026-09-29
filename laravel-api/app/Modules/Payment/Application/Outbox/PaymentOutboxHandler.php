<?php

namespace App\Modules\Payment\Application\Outbox;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;

final class PaymentOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OutboxRepositoryInterface $outbox,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'payment.create.requested';
    }

    public function handle(object $event): void
    {
        $payment = $this->payments->find((int) $event->aggregate_id);
        if (in_array($payment->status, ['provider_created', 'confirmed', 'paid', 'refunded', 'failed'], true)) {
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

            return;
        }

        $key = (string) $payment->idempotency_key;
        $operationToken = (string) $event->claim_token;
        $this->operations->start((int) $payment->id, 'create', $key);
        if (! $this->operations->acquireLease((int) $payment->id, 'create', $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
            return;
        }
        $previous = $this->operations->successfulResponse((int) $payment->id, 'create');
        if ($previous !== null) {
            if (! $this->outbox->ownsClaim((int) $event->id, $operationToken)) return;
            $this->payments->updateStatus($payment, $previous['_operation_status'] ?? 'provider_created', [
                'provider_reference' => $previous['provider_reference'] ?? null,
                'metadata' => $previous['metadata'] ?? $payment->metadata,
            ]);
            $this->operations->releaseLease((int) $payment->id, 'create', $operationToken);
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

            return;
        }

        $result = $this->gateway->createPayment($payment->order, (string) $payment->method, $key);
        $status = ($result['status'] ?? null) === 'paid'
            ? 'confirmed'
            : (($result['provider_reference'] ?? null) !== null ? 'provider_created' : 'pending');
        if (! $this->operations->ownsLease((int) $payment->id, 'create', $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
            return;
        }
        $this->payments->updateStatus($payment, $status, [
            'provider_reference' => $result['provider_reference'] ?? null,
            'metadata' => $result['metadata'] ?? $payment->metadata,
        ]);
        $this->operations->complete((int) $payment->id, 'create', $status, $result['provider_reference'] ?? null, $result, $operationToken);
        $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $retryable = true;
        if ($this->operations->ownsLease((int) $event->aggregate_id, 'create', (string) $event->claim_token)) {
            $this->operations->fail((int) $event->aggregate_id, 'create', $exception->getMessage(), $retryable, (string) $event->claim_token);
        }
        $exhausted = $this->outbox->markFailed((int) $event->id, (string) $event->claim_token, $exception->getMessage());
        if ($exhausted) {
            $payment = $this->payments->find((int) $event->aggregate_id);
            if ($payment->status === 'processing') $this->payments->updateStatus($payment, 'failed');
        }
    }
}
