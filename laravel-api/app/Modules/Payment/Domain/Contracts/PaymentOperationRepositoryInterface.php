<?php

namespace App\Modules\Payment\Domain\Contracts;

interface PaymentOperationRepositoryInterface
{
    public function start(int $paymentId, string $operation, string $idempotencyKey, ?int $requestedAmount = null): void;

    public function acquireLease(int $paymentId, string $operation, string $token, int $seconds = 300, ?string $idempotencyKey = null, bool $allowAmbiguous = false): bool;

    public function releaseLease(int $paymentId, string $operation, string $token): void;

    public function ownsLease(int $paymentId, string $operation, string $token): bool;

    public function successfulResponse(int $paymentId, string $operation, ?string $idempotencyKey = null): ?array;

    public function hasAttempted(int $paymentId, string $operation): bool;

    public function ambiguousOperation(int $paymentId, int $operationId): ?string;

    public function requestedAmount(int $paymentId, string $operation): ?int;

    public function refundedAmount(int $paymentId): int;

    public function complete(int $paymentId, string $operation, string $status, ?string $providerReference, array $response, ?string $leaseToken = null, ?int $confirmedAmount = null): bool;

    public function fail(int $paymentId, string $operation, string $error, bool $retryable = true, ?string $leaseToken = null): bool;
    public function failAmbiguous(int $paymentId, string $operation, string $error, ?string $leaseToken = null): bool;
}
