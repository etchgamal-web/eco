<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Models\PaymentOperation;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;

final class EloquentPaymentOperationRepository implements PaymentOperationRepositoryInterface
{
    public function start(int $paymentId, string $operation, string $idempotencyKey): void
    {
        $operationRecord = PaymentOperation::query()->firstOrNew([
            'payment_id' => $paymentId,
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
        ]);
        $operationRecord->status = 'processing';
        $operationRecord->attempt_count = ((int) $operationRecord->attempt_count) + 1;
        $operationRecord->next_retry_at = null;
        $operationRecord->save();
    }

    public function acquireLease(int $paymentId, string $operation, string $token, int $seconds = 300): bool
    {
        $now = now();
        return PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)
            ->where(function ($query) use ($token, $now): void {
                $query->whereNull('lease_token')->orWhere('lease_expires_at', '<=', $now)->orWhere('lease_token', $token);
            })->update(['lease_token' => $token, 'lease_expires_at' => $now->addSeconds($seconds)]) === 1;
    }

    public function releaseLease(int $paymentId, string $operation, string $token): void
    {
        PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->where('lease_token', $token)
            ->update(['lease_token' => null, 'lease_expires_at' => null]);
    }

    public function successfulResponse(int $paymentId, string $operation): ?array
    {
        $record = PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->first();
        if (! in_array($record?->status, ['provider_created', 'confirmed'], true)) {
            return null;
        }
        return array_merge((array) $record->response_payload, ['_operation_status' => $record->status]);
    }

    public function complete(int $paymentId, string $operation, string $status, ?string $providerReference, array $response): void
    {
        PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->update([
            'status' => $status,
            'provider_reference' => $providerReference,
            'response_payload' => $response,
            'last_error' => null,
            'next_retry_at' => null,
            'lease_token' => null,
            'lease_expires_at' => null,
        ]);
    }

    public function fail(int $paymentId, string $operation, string $error, bool $retryable = true): void
    {
        PaymentOperation::query()->where('payment_id', $paymentId)->where('operation', $operation)->update([
            'status' => $retryable ? 'processing' : 'failed',
            'last_error' => $error,
            'next_retry_at' => $retryable ? now()->addMinutes(5) : null,
            'lease_token' => null,
            'lease_expires_at' => null,
        ]);
    }
}
