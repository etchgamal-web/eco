<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class VariantNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Product variant [{$id}] was not found."); }
}
