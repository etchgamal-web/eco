<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Payment\Domain\Contracts\KashierWebhookVerifierInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentWebhookEventRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;

final class ProcessKashierWebhook
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly TransactionManagerInterface $transactions,
        private readonly KashierWebhookVerifierInterface $verifier,
        private readonly PaymentWebhookEventRepositoryInterface $events,
    ) {}

    public function execute(array $payload, string $signature = ''): ?object
    {
        if (! $this->verifier->verify($payload, $signature)) {
            throw new PaymentException('Invalid Kashier webhook signature.');
        }

        $data = (array) ($payload['data'] ?? []);
        $eventType = (string) ($payload['event'] ?? 'payment');
        $transactionId = (string) ($data['transactionId'] ?? $data['kashierOrderId'] ?? '');
        $providerOrderId = (string) ($data['kashierOrderId'] ?? '');
        $merchantOrderId = (string) ($data['merchantOrderId'] ?? $data['orderReference'] ?? '');
        $providerStatus = strtoupper((string) ($data['status'] ?? ''));
        if ($transactionId === '' || $merchantOrderId === '') {
            throw new PaymentException('Kashier webhook is missing its payment reference.');
        }
        if (! in_array($providerStatus, ['SUCCESS', 'FAILURE', 'PENDING'], true)) {
            throw new PaymentException('Kashier webhook contains an unsupported payment status.');
        }

        $eventId = hash('sha256', implode('|', [$eventType, $transactionId, $providerStatus]));

        $event = $this->events->recordOrGet([
            'provider' => 'kashier',
            'event_id' => $eventId,
            'event_type' => $eventType,
            'status' => 'received',
            'payment_reference' => $providerOrderId !== '' ? $providerOrderId : $eventId,
            'payload' => $payload,
        ]);
        if ($event->status === 'processed') {
            return null;
        }
        if (! isset($data['amount'], $data['currency'])) {
            throw new PaymentException('Kashier webhook event payload is invalid.');
        }

        $payment = $this->payments->findByIdempotencyKey($merchantOrderId);
        $payment ??= $this->payments->findByProviderReference($providerOrderId);
        if ($payment === null) {
            throw new PaymentException('Kashier webhook does not match a local payment.');
        }
        if (abs((float) $data['amount'] - (float) $payment->amount) > 0.001 || strtoupper((string) $data['currency']) !== strtoupper((string) $payment->currency)) {
            throw new PaymentAmountMismatchException('Kashier webhook amount or currency does not match the local payment.');
        }

        $status = match ($providerStatus) {
            'SUCCESS' => 'confirmed',
            'FAILURE' => 'failed',
            default => 'pending',
        };
        if ($status === 'pending' && $payment->status === 'provider_created') {
            $status = 'provider_created';
        }
        if (in_array($payment->status, ['confirmed', 'paid', 'refunded'], true) && $status !== 'confirmed') {
            $this->events->markProcessed('kashier', $eventId);

            return $payment;
        }
        $metadata = array_merge((array) $payment->metadata, [
            'provider' => 'kashier',
            'transaction_id' => $data['transactionId'] ?? null,
            'kashier_order_id' => $data['kashierOrderId'] ?? null,
            'webhook' => $payload,
        ]);

        return $this->transactions->run(function () use ($payment, $status, $metadata, $eventId, $eventType, $data): object {
            $locked = $this->payments->findForUpdate((int) $payment->id);
            $updated = $this->payments->updateStatus($locked, $status, ['metadata' => $metadata]);
            $operationStatus = match ($status) {
                'confirmed' => 'confirmed',
                'failed' => 'failed',
                'provider_created' => 'provider_created',
                default => 'processing',
            };
            $this->operations->complete((int) $updated->id, 'create', $operationStatus, (string) ($data['transactionId'] ?? $data['kashierOrderId'] ?? ''), ['event' => $eventType, 'data' => $data]);
            $this->events->markProcessed('kashier', $eventId);

            return $updated;
        });
    }
}
