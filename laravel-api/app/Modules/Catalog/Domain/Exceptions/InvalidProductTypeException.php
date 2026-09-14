<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class InvalidProductTypeException extends BusinessRuleException
{
    public static function unsupported(string $type): self { return new self("Unsupported product type [{$type}]."); }
    public static function variantNotAllowed(): self { return new self('A simple product cannot have variants.'); }
    public static function hasVariants(): self { return new self('A product with variants cannot be changed to simple.'); }
}
