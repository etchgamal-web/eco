<?php

namespace App\Modules\Payment\Application\Outbox;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Shared\Application\Outbox\OutboxEventHandlerInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class PaymentOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OutboxEventRepositoryInterface $outbox,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'payment.create.requested';
    }

    public function handle(object $event): void
    {
        $payment = $this->payments->find((int) $event->aggregate_id);
        if (in_array($payment->status, ['provider_created', 'confirmed', 'paid', 'refunded', 'failed'], true)) {
            $this->outbox->markDispatched((string) $event->deduplication_key);

            return;
        }

        $key = (string) $payment->idempotency_key;
        $previous = $this->operations->successfulResponse((int) $payment->id, 'create');
        if ($previous !== null) {
            $this->payments->updateStatus($payment, $previous['_operation_status'] ?? 'provider_created', [
                'provider_reference' => $previous['provider_reference'] ?? null,
                'metadata' => $previous['metadata'] ?? $payment->metadata,
            ]);
            $this->outbox->markDispatched((string) $event->deduplication_key);

            return;
        }

        $this->operations->start((int) $payment->id, 'create', $key);
        $result = $this->gateway->createPayment($payment->order, (string) $payment->method, $key);
        $status = ($result['status'] ?? null) === 'paid'
            ? 'confirmed'
            : (($result['provider_reference'] ?? null) !== null ? 'provider_created' : 'pending');
        $this->operations->complete((int) $payment->id, 'create', $status, $result['provider_reference'] ?? null, $result);
        $this->payments->updateStatus($payment, $status, [
            'provider_reference' => $result['provider_reference'] ?? null,
            'metadata' => $result['metadata'] ?? $payment->metadata,
        ]);
        $this->outbox->markDispatched((string) $event->deduplication_key);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $this->outbox->markFailed((string) $event->deduplication_key, $exception->getMessage());
    }
}
