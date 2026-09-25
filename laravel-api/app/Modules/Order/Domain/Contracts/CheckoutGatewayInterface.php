<?php

namespace App\Modules\Order\Domain\Contracts;

interface CheckoutGatewayInterface
{
    public function findByIdempotencyKey(string $key): ?object;

    public function productForGuest(int $productId): ?object;

    public function createOrder(array $attributes): object;

    public function createOrderItems(object $order, array $items): void;

    public function recordCouponUsage(string $code, int $userId, int $orderId, int $discount): void;

}
