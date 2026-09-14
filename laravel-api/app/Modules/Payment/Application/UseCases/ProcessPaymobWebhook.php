<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Contracts\PaymobWebhookVerifierInterface;
use App\Modules\Payment\Domain\Contracts\PaymentWebhookEventRepositoryInterface;

final class ProcessPaymobWebhook
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly PaymobWebhookVerifierInterface $verifier,
        private readonly PaymentWebhookEventRepositoryInterface $events,
    ) {
    }

    public function execute(array $payload, string $hmac): ?object
    {
        if (! $this->verifier->verify($payload, $hmac)) {
            throw new PaymentException('Invalid Paymob webhook signature.');
        }

        $object = (array) ($payload['obj'] ?? $payload);
        $eventId = (string) ($object['id'] ?? $payload['id'] ?? '');
        $reference = (string) ($object['id'] ?? '');
        if ($eventId === '' || $reference === '') {
            throw new PaymentException('Paymob webhook is missing its event reference.');
        }

        $event = $this->events->recordOrGet([
                'provider' => 'paymob',
                'event_id' => $eventId,
                'event_type' => (string) ($payload['type'] ?? 'TRANSACTION'),
                'status' => 'received',
                'payment_reference' => $reference,
                'payload' => $payload,
            ]);
        if ($event->status === 'processed') {
            return null;
        }
        if (! array_key_exists('success', $object) && ! array_key_exists('pending', $object)) {
            throw new PaymentException('Paymob webhook event payload is invalid.');
        }

        $merchantReference = (string) data_get($object, 'order.merchant_order_id', '');
        $payment = $merchantReference !== ''
            ? $this->payments->findByIdempotencyKey($merchantReference)
            : null;
        $payment ??= $this->payments->findByProviderReference($reference);
        if ($payment === null) {
            throw new PaymentException('Paymob webhook does not match a local payment.');
        }
        if ((int) ($object['amount_cents'] ?? $payload['amount_cents'] ?? -1) !== (int) $payment->amount) {
            throw new PaymentAmountMismatchException('Paymob webhook amount does not match the local payment.');
        }

        $pending = (bool) ($object['pending'] ?? false);
        $status = $pending ? 'pending' : ((bool) ($object['success'] ?? false) ? 'confirmed' : 'failed');
        if ($status === 'pending' && $payment->status === 'provider_created') {
            $status = 'provider_created';
        }
        if (in_array($payment->status, ['confirmed', 'paid', 'refunded'], true) && $status !== 'confirmed') {
            $this->events->markProcessed('paymob', $eventId);
            return $payment;
        }
        $metadata = array_merge((array) $payment->metadata, [
            'provider' => 'paymob',
            'transaction_id' => $reference,
            'webhook' => $payload,
        ]);

        $result = $this->transactions->run(function () use ($payment, $status, $metadata, $eventId, $reference, $payload): object {
            $locked = $this->payments->findForUpdate((int) $payment->id);
            $updated = $this->payments->updateStatus($locked, $status, ['metadata' => $metadata]);
            $this->operations->complete((int) $updated->id, 'create', $status === 'confirmed' ? 'confirmed' : ($status === 'failed' ? 'failed' : ($status === 'provider_created' ? 'provider_created' : 'processing')), $reference, $payload);
            if ($status === 'confirmed' && $updated->order->status === 'pending') {
                $this->orders->updateStatus((int) $updated->order_id, 'confirmed');
            }
            $this->events->markProcessed('paymob', $eventId);
            return $updated;
        });

        return $result;
    }
}
