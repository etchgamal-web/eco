<?php
namespace App\Modules\Order\Domain\Exceptions;
use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class ReturnException extends BusinessRuleException
{
    public static function notAllowed(): self { return new self('This order is not eligible for return.'); }
    public static function invalidItems(): self { return new self('Return items or quantities are invalid.'); }
    public static function alreadyRequested(): self { return new self('A return request already exists for this order.'); }
    public static function invalidTransition(): self { return new self('This return request cannot be changed.'); }
}
