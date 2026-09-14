<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class CategoryNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Category [{$id}] was not found."); }
}
