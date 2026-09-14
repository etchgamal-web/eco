<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Contracts\KashierWebhookVerifierInterface;
use App\Modules\Payment\Domain\Contracts\PaymentWebhookEventRepositoryInterface;

final class ProcessKashierWebhook
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly KashierWebhookVerifierInterface $verifier,
        private readonly PaymentWebhookEventRepositoryInterface $events,
    ) {
    }

    public function execute(array $payload): ?object
    {
        if (! $this->verifier->verify($payload)) {
            throw new PaymentException('Invalid Kashier webhook signature.');
        }

        $eventId = (string) ($payload['transactionId'] ?? $payload['orderId'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? $payload['orderReference'] ?? '');
        if ($eventId === '' || $merchantOrderId === '') {
            throw new PaymentException('Kashier webhook is missing its payment reference.');
        }

        $event = $this->events->recordOrGet([
                'provider' => 'kashier',
                'event_id' => $eventId,
                'event_type' => 'payment',
                'status' => 'received',
                'payment_reference' => (string) ($payload['orderId'] ?? $eventId),
                'payload' => $payload,
            ]);
        if ($event->status === 'processed') {
            return null;
        }
        if (! array_key_exists('paymentStatus', $payload) || ! array_key_exists('amount', $payload)) {
            throw new PaymentException('Kashier webhook event payload is invalid.');
        }

        $payment = $this->payments->findByIdempotencyKey($merchantOrderId);
        $payment ??= $this->payments->findByProviderReference((string) ($payload['orderId'] ?? ''));
        if ($payment === null) {
            throw new PaymentException('Kashier webhook does not match a local payment.');
        }
        if (abs((float) ($payload['amount'] ?? -1) - (float) $payment->amount) > 0.001) {
            throw new PaymentAmountMismatchException('Kashier webhook amount does not match the local payment.');
        }

        $paid = strtoupper((string) ($payload['paymentStatus'] ?? '')) === 'SUCCESS';
        $status = $paid ? 'confirmed' : 'failed';
        if (in_array($payment->status, ['confirmed', 'paid', 'refunded'], true) && $status !== 'confirmed') {
            $this->events->markProcessed('kashier', $eventId);
            return $payment;
        }
        $metadata = array_merge((array) $payment->metadata, [
            'provider' => 'kashier',
            'transaction_id' => $payload['transactionId'] ?? null,
            'kashier_order_id' => $payload['orderId'] ?? null,
            'webhook' => $payload,
        ]);

        return $this->transactions->run(function () use ($payment, $status, $metadata, $eventId, $payload): object {
            $locked = $this->payments->findForUpdate((int) $payment->id);
            $updated = $this->payments->updateStatus($locked, $status, ['metadata' => $metadata]);
            $this->operations->complete((int) $updated->id, 'create', $status === 'confirmed' ? 'confirmed' : 'failed', (string) ($payload['transactionId'] ?? $payload['orderId'] ?? ''), $payload);
            if ($status === 'confirmed' && $updated->order->status === 'pending') {
                $this->orders->updateStatus((int) $updated->order_id, 'confirmed');
            }
            $this->events->markProcessed('kashier', $eventId);
            return $updated;
        });
    }
}
