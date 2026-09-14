<?php

namespace App\Modules\Order\Domain\Exceptions;

use RuntimeException;

final class InvalidOrderStatusTransitionException extends RuntimeException
{
    public static function from(string $from, string $to): self
    {
        return new self("Order cannot transition from [{$from}] to [{$to}].");
    }
}
