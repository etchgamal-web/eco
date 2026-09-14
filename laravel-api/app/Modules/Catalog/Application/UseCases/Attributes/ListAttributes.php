<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;

use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use Illuminate\Support\Collection;

final class ListAttributes
{
    public function __construct(private readonly AttributeRepositoryInterface $attributes) {}

    public function execute(): Collection
    {
        return $this->attributes->all();
    }
}
