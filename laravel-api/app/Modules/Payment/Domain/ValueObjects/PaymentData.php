<?php

namespace App\Modules\Payment\Domain\ValueObjects;

final readonly class PaymentData
{
    public function __construct(
        public string $method,
        public string $currency,
        public string $idempotencyKey,
        public ?int $amount = null,
    ) {}
}
