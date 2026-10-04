<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentNotFoundException;
use App\Modules\Payment\Domain\StateMachines\PaymentStateMachine;
use App\Modules\Payment\Domain\ValueObjects\PaymentClaim;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private readonly OutboxRepositoryInterface $outbox) {}

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

    public function listForAdmin(array $filters): array
    {
        $query = Payment::query()->with(['order:id,order_number', 'user:id,name,email'])->latest();
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['method'])) $query->where('method', $filters['method']);
        if (! empty($filters['provider'])) $query->where('provider_reference', 'like', '%'.$filters['provider'].'%');
        if (! empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        $page = max(1, (int) ($filters['page'] ?? 1)); $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 25)));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        return ['data' => $paginator->getCollection()->map(fn (Payment $payment) => ['id' => $payment->id, 'order_id' => $payment->order_id, 'order_number' => $payment->order?->order_number, 'customer' => $payment->user?->name ?? $payment->user?->email, 'method' => $payment->method, 'provider_reference' => $payment->provider_reference, 'amount' => (int) $payment->amount, 'currency' => $payment->currency, 'status' => $payment->status, 'created_at' => $payment->created_at?->toIso8601String()])->all(), 'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]];
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
                $this->outbox->add(new OutboxMessage('payment.create.requested', 'payment', (int) $payment->id, ['payment_id' => $payment->id, 'idempotency_key' => $idempotencyKey], deduplicationKey: 'payment:create:'.$idempotencyKey));

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

    public function updateStatus(object $payment, string $status, array $attributes = [], ?string $operationLeaseToken = null): object
    {
        if ($operationLeaseToken === null) {
            PaymentStateMachine::assert((string) $payment->status, $status);
            $payment->update(array_merge($attributes, ['status' => $status]));
            return $payment->fresh(['order']);
        }
        return DB::transaction(function () use ($payment, $status, $attributes, $operationLeaseToken): object {
            $owns = PaymentOperation::query()->lockForUpdate()->where('payment_id', $payment->id)->where('operation', 'create')->where('lease_token', $operationLeaseToken)->where('lease_expires_at', '>', now())->exists();
            if (! $owns) throw new \RuntimeException('Payment operation lease is no longer valid.');
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            PaymentStateMachine::assert((string) $locked->status, $status);
            $locked->update(array_merge($attributes, ['status' => $status]));
            return $locked->fresh(['order']);
        });
    }
}
