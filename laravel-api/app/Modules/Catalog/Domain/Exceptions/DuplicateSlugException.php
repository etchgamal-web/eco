<?php
namespace App\Modules\Catalog\Domain\Exceptions;
final class DuplicateSlugException extends BusinessRuleException
{
    public function __construct(string $slug) { parent::__construct("The slug [{$slug}] is already in use."); }
}
