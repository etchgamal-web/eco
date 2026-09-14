<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Models\Payment;
use App\Models\OutboxEvent;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use App\Modules\Payment\Domain\ValueObjects\PaymentClaim;
use App\Modules\Payment\Domain\StateMachines\PaymentStateMachine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function find(int $paymentId): object
    {
        $payment = Payment::query()->with('order')->find($paymentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment not found.');
        }

        return $payment;
    }

    public function findForUpdate(int $paymentId): object
    {
        $payment = Payment::query()->with('order')->lockForUpdate()->find($paymentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment not found.');
        }

        return $payment;
    }

    public function findForUserOrder(int $userId, int $orderId, int $paymentId): object
    {
        $payment = Payment::query()->with('order')->where('user_id', $userId)->where('order_id', $orderId)->find($paymentId);
        if ($payment === null) {
            throw new PaymentNotFoundException('Payment not found.');
        }

        return $payment;
    }

    public function findByIdempotencyKey(string $key): ?object
    {
        return Payment::query()->with('order')->where('idempotency_key', $key)->first();
    }

    public function findByProviderReference(string $reference): ?object
    {
        return Payment::query()->with('order')->where('provider_reference', $reference)->first();
    }

    public function listForOrder(int $userId, int $orderId): iterable
    {
        return Payment::query()->where('user_id', $userId)->where('order_id', $orderId)->latest()->get();
    }

    public function listForOrderAsAdmin(int $orderId): iterable
    {
        return Payment::query()->with('order')->where('order_id', $orderId)->latest()->get();
    }

    public function create(array $attributes): object
    {
        return Payment::query()->create($attributes)->load('order');
    }

    public function start(array $attributes): object
    {
        try {
            return $this->create($attributes);
        } catch (QueryException $exception) {
            // The unique idempotency constraint is the distributed lock. If two
            // requests race, return the row committed by the winner.
            $existing = $this->findByIdempotencyKey((string) $attributes['idempotency_key']);
            if ($existing !== null) {
                return $existing;
            }
            throw $exception;
        }
    }

    public function claim(string $idempotencyKey, array $attributes): PaymentClaim
    {
        return DB::transaction(function () use ($idempotencyKey, $attributes): PaymentClaim {
            $existing = Payment::query()->with('order')->lockForUpdate()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return new PaymentClaim($existing, false);
            }

            try {
                $payment = Payment::query()->create(array_merge($attributes, [
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'processing',
                ]))->load('order');
                OutboxEvent::query()->firstOrCreate(
                    ['deduplication_key' => 'payment:create:' . $idempotencyKey],
                    [
                        'aggregate_type' => 'payment',
                        'aggregate_id' => $payment->id,
                        'event_type' => 'payment.create.requested',
                        'status' => 'pending',
                        'payload' => ['payment_id' => $payment->id, 'idempotency_key' => $idempotencyKey],
                    ]
                );

                return new PaymentClaim($payment, true);
            } catch (QueryException $exception) {
                $existing = Payment::query()->with('order')->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return new PaymentClaim($existing, false);
                }
                throw $exception;
            }
        });
    }

    public function updateStatus(object $payment, string $status, array $attributes = []): object
    {
        PaymentStateMachine::assert((string) $payment->status, $status);
        $payment->update(array_merge($attributes, ['status' => $status]));

        return $payment->fresh(['order']);
    }
}
