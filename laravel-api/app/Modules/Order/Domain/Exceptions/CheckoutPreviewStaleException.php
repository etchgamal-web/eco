<?php

namespace App\Modules\Order\Domain\Exceptions;

use RuntimeException;

final class CheckoutPreviewStaleException extends RuntimeException
{
    /** @param array<string, mixed> $quote */
    public function __construct(public readonly array $quote)
    {
        parent::__construct('Checkout details changed since preview. Review the updated totals and confirm again.');
    }
}
