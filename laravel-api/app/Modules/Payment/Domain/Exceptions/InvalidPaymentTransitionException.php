<?php

namespace App\Modules\Payment\Domain\Exceptions;

final class InvalidPaymentTransitionException extends PaymentException
{
    public static function from(string $from, string $to): self
    {
        return new self("Payment cannot transition from [{$from}] to [{$to}].");
    }
}
