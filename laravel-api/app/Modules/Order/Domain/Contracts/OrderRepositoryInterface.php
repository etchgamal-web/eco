<?php

namespace App\Modules\Order\Domain\Contracts;

interface OrderRepositoryInterface
{
    public function checkout(int $userId, int $addressId, string $currency, ?string $idempotencyKey, ?string $couponCode = null): object;

    public function checkoutGuest(array $items, array $details, string $currency, ?string $idempotencyKey, ?string $couponCode = null): object;

    public function listForUser(int $userId): iterable;

    public function listAll(): iterable;

    public function findForUser(int $userId, int $orderId): object;

    public function find(int $orderId): object;

    public function updateStatus(int $orderId, string $status): object;

    public function cancelForUser(int $userId, int $orderId): object;

    public function cancel(int $orderId): object;

    public function addShippingFee(int $orderId, int $fee): object;

    public function markRefunded(int $orderId): object;
}
