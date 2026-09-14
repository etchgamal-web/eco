<?php

namespace App\Modules\Payment\Domain\Contracts;

use App\Modules\Payment\Domain\ValueObjects\PaymentClaim;

interface PaymentRepositoryInterface
{
    public function find(int $paymentId): object;
    public function findForUpdate(int $paymentId): object;
    public function findForUserOrder(int $userId, int $orderId, int $paymentId): object;
    public function findByIdempotencyKey(string $key): ?object;
    public function findByProviderReference(string $reference): ?object;
    public function listForOrder(int $userId, int $orderId): iterable;
    public function listForOrderAsAdmin(int $orderId): iterable;
    public function create(array $attributes): object;
    public function start(array $attributes): object;
    public function claim(string $idempotencyKey, array $attributes): PaymentClaim;
    public function updateStatus(object $payment, string $status, array $attributes = []): object;
}
