<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class AttributeNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Attribute [{$id}] was not found."); }
}
