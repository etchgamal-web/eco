<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class InvalidVariantCombinationException extends BusinessRuleException
{
    public static function duplicate(): self { return new self('The variant attribute combination already exists.'); }
    public static function invalid(): self { return new self('The variant attribute values must exist and contain one value per attribute.'); }
}
