<?php
namespace App\Modules\Catalog\Domain\Exceptions;
use RuntimeException;
final class AttributeValueNotFoundException extends RuntimeException
{
    public function __construct(int $id) { parent::__construct("Attribute value [{$id}] was not found."); }
}
