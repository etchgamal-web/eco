<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class ReviewNotAllowedException extends BusinessRuleException
{
    public static function notPurchased(): self { return new self('You can review a product only after purchasing it.'); }
    public static function alreadyReviewed(): self { return new self('You have already reviewed this product.'); }
    public static function invalidStatus(): self { return new self('The review status is invalid.'); }
}
