<?php

namespace App\Modules\Promotion\Domain\Exceptions;

use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;

final class CouponInvalidException extends BusinessRuleException
{
    public static function forCode(string $code): self
    {
        return new self("Coupon [{$code}] is invalid, expired, exhausted, or not applicable.");
    }
}
