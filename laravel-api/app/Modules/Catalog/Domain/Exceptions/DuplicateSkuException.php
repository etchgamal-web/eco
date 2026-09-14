<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class DuplicateSkuException extends BusinessRuleException
{
    public function __construct(string $sku) { parent::__construct("The SKU [{$sku}] is already in use."); }
}
