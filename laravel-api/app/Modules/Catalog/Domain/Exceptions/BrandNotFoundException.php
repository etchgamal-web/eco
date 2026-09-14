<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class BrandNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Brand [{$id}] was not found."); }
}
