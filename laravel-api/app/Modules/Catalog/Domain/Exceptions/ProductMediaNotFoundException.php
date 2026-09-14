<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class ProductMediaNotFoundException extends BusinessRuleException
{
    public function __construct(int $id) { parent::__construct("Product media [{$id}] was not found."); }
}
