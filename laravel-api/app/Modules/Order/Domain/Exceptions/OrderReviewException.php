<?php

namespace App\Modules\Order\Domain\Exceptions;

use RuntimeException;

final class OrderReviewException extends RuntimeException
{
    public static function contactRequired(): self
    {
        return new self('A confirmed customer contact result is required before confirmation.');
    }

    public static function notFound(): self
    {
        return new self('Order review not found.');
    }
}

