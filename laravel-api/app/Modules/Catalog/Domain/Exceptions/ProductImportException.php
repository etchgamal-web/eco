<?php

namespace App\Modules\Catalog\Domain\Exceptions;

final class ProductImportException extends BusinessRuleException
{
    public static function invalidHeaders(array $missing): self
    {
        return new self('Product import is missing required columns: '.implode(', ', $missing).'.');
    }

    public static function invalidRow(int $row, string $reason): self
    {
        return new self("Product import row {$row} is invalid: {$reason}");
    }

    public static function unreadableFile(): self
    {
        return new self('The product import file could not be read.');
    }
}
