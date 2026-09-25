<?php

namespace App\Modules\Order\Domain\Contracts;

interface PaymentInitiatorInterface
{
    public function initiate(
        int $orderId,
        string $method,
        string $currency,
        string $idempotencyKey,
        ?int $amount = null,
    ): object;
}
