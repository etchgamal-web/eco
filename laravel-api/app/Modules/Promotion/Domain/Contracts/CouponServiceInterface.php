<?php

namespace App\Modules\Promotion\Domain\Contracts;

interface CouponServiceInterface
{
    /** @return array{code:?string, discount:int} */
    public function apply(?string $code, int $userId, int $subtotal): array;

    public function recordUsage(string $code, int $userId, int $orderId, int $discount): void;
}
