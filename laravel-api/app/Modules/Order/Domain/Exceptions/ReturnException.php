<?php

namespace App\Modules\Order\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;

final class ReturnException extends BusinessRuleException
{
    public static function notAllowed(): self
    {
        return new self('This order is not eligible for return.');
    }

    public static function invalidItems(): self
    {
        return new self('Return items or quantities are invalid.');
    }

    public static function alreadyRequested(): self
    {
        return new self('A return request already exists for this order.');
    }

    public static function refundableAmountExceeded(): self
    {
        return new self('The requested refund exceeds the remaining refundable amount for this order payment.');
    }

    public static function itemQuantityExceeded(): self
    {
        return new self('The requested return quantity exceeds the quantity still eligible for return.');
    }

    public static function invalidTransition(): self
    {
        return new self('This return request cannot be changed.');
    }
}
