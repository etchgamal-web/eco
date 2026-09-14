<?php

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\ValueObjects\AttributeValueData;

interface AttributeValueRepositoryInterface
{
    public function forAttribute(int $attributeId): iterable;
    public function findOrFail(int $id, ?int $attributeId = null): object;
    public function findMany(array $ids): iterable;
    public function valueExists(int $attributeId, string $value, ?int $exceptId = null): bool;
    public function create(AttributeValueData $data): object;
    public function update(int $valueId, AttributeValueData $data): object;
    public function isUsed(int $valueId): bool;
    public function delete(int $valueId): void;
}
