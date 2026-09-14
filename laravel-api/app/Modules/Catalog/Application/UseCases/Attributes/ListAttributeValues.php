<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;

use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use Illuminate\Support\Collection;

final class ListAttributeValues
{
    public function __construct(
        private readonly AttributeRepositoryInterface $attributes,
        private readonly AttributeValueRepositoryInterface $values,
    ) {}

    public function execute(int $attributeId): Collection
    {
        $this->attributes->findOrFail($attributeId);

        return $this->values->forAttribute($attributeId);
    }
}
