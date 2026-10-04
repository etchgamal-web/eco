<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use Illuminate\Database\Eloquent\Builder;

final class EloquentPaymentOperationRepository implements PaymentOperationRepositoryInterface
{
    public function start(int $paymentId, string $operation, string $idempotencyKey, ?int $requestedAmount = null, ?int $returnId = null): void
    {
        $record = PaymentOperation::query()->firstOrNew(['payment_id' => $paymentId, 'operation' => $operation, 'idempotency_key' => $idempotencyKey], ['return_id' => $returnId]);
        if ($record->exists && in_array($record->status, ['provider_created', 'confirmed', 'failed', 'ambiguous'], true)) {
            return;
        }
        $record->status = 'processing';
        $record->attempt_count = ((int) $record->attempt_count) + 1;
        if ($requestedAmount !== null) {
            $record->requested_amount = $requestedAmount;
        }
        $record->next_retry_at = null;
        $record->save();
    }

    public function acquireLease(int $paymentId, string $operation, string $token, int $seconds = 300, ?string $idempotencyKey = null, bool $allowAmbiguous = false, ?int $operationId = null): bool
    {
        $now = now();

        return PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->when($operationId !== null, fn ($query) => $query->whereKey($operationId))->when($idempotencyKey !== null, fn ($query) => $query->where('idempotency_key', $idempotencyKey))->when(! $allowAmbiguous, fn ($query) => $query->where('status', '!=', 'ambiguous'))
            ->where(function (Builder $query) use ($token, $now): void {
                $query->whereNull('lease_token')->orWhere('lease_expires_at', '<=', $now)->orWhere('lease_token', $token);
            })->update(['lease_token' => $token, 'lease_expires_at' => $now->addSeconds($seconds)]) === 1;
    }

    public function releaseLease(int $paymentId, string $operation, string $token): void
    {
        PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->where('lease_token', $token)->update(['lease_token' => null, 'lease_expires_at' => null]);
    }

    public function ownsLease(int $paymentId, string $operation, string $token): bool
    {
        return PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->where('lease_token', $token)->where('lease_expires_at', '>', now())->exists();
    }

    public function successfulResponse(int $paymentId, string $operation, ?string $idempotencyKey = null): ?array
    {
        $record = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->when($idempotencyKey !== null, fn ($query) => $query->where('idempotency_key', $idempotencyKey))->latest('id')->first();
        if (! in_array($record?->status, ['provider_created', 'confirmed'], true)) {
            return null;
        }

        return array_merge((array) $record->response_payload, ['_operation_status' => $record->status, 'requested_amount' => $record->requested_amount, 'confirmed_amount' => $record->confirmed_amount]);
    }

    public function hasAttempted(int $paymentId, string $operation): bool
    {
        return PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->where('attempt_count', '>', 0)->exists();
    }

    public function ambiguousOperation(int $paymentId, int $operationId): ?string
    {
        return PaymentOperation::query()->whereKey($operationId)->where('payment_id', $paymentId)->where('status', 'ambiguous')->value('operation');
    }

    public function operation(int $paymentId, int $operationId): ?object
    {
        return PaymentOperation::query()->whereKey($operationId)->where('payment_id', $paymentId)->where('status', 'ambiguous')->first();
    }

    public function requestedAmount(int $paymentId, string $operation, ?int $operationId = null): ?int
    {
        $amount = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->when($operationId !== null, fn ($query) => $query->whereKey($operationId))->value('requested_amount');

        return $amount === null ? null : (int) $amount;
    }

    public function returnId(int $paymentId, int $operationId): ?int
    {
        $returnId = PaymentOperation::query()->whereKey($operationId)->where('payment_id', $paymentId)->value('return_id');

        return $returnId === null ? null : (int) $returnId;
    }

    public function refundedAmount(int $paymentId): int
    {
        return (int) PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', 'refund')->whereIn('status', ['confirmed', 'refunded'])->sum('confirmed_amount');
    }

    public function complete(int $paymentId, string $operation, string $status, ?string $providerReference, array $response, ?string $leaseToken = null, ?int $confirmedAmount = null): bool
    {
        $query = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation);
        if ($leaseToken !== null) {
            $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        }

        return $query->update(['status' => $status, 'provider_reference' => $providerReference, 'response_payload' => $response, 'confirmed_amount' => $confirmedAmount, 'last_error' => null, 'next_retry_at' => null, 'last_reconciliation_at' => now(), 'next_reconciliation_at' => null, 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }

    public function fail(int $paymentId, string $operation, string $error, bool $retryable = true, ?string $leaseToken = null): bool
    {
        $query = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation);
        if ($leaseToken !== null) {
            $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        }

        return $query->update(['status' => $retryable ? 'processing' : 'failed', 'last_error' => $error, 'next_retry_at' => $retryable ? now()->addMinutes(5) : null, 'last_reconciliation_at' => now(), 'next_reconciliation_at' => null, 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }

    public function failAmbiguous(int $paymentId, string $operation, string $error, ?string $leaseToken = null): bool
    {
        $query = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation);
        if ($leaseToken !== null) {
            $query->where('lease_token', $leaseToken)->where('lease_expires_at', '>', now());
        }

        return $query->update(['status' => 'ambiguous', 'last_error' => $error, 'next_retry_at' => null, 'last_reconciliation_at' => now(), 'next_reconciliation_at' => $operation === 'refund' ? now()->addMinutes(max(1, (int) config('payments.refund_reconciliation_retry_minutes', 15))) : null, 'lease_token' => null, 'lease_expires_at' => null]) === 1;
    }
}
