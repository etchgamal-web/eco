<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class ProductNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Product [{$id}] was not found."); }
}
