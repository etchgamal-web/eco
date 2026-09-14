<?php

namespace App\Modules\Order\Domain\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Cannot checkout an empty cart.');
    }

    public static function unavailableProduct(string $name): self
    {
        return new self("Product [{$name}] is not available for purchase.");
    }

    public static function missingPrice(string $name): self
    {
        return new self("Product [{$name}] has no valid price.");
    }

    public static function idempotencyKeyConflict(): self
    {
        return new self('The checkout idempotency key is already associated with another order.');
    }
}
