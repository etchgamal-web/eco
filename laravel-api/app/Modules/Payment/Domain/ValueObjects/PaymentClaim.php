<?php

namespace App\Modules\Payment\Domain\ValueObjects;

final readonly class PaymentClaim
{
    public function __construct(
        public object $payment,
        public bool $acquired,
    ) {}
}
