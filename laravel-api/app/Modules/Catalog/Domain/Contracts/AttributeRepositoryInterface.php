<?php

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\ValueObjects\AttributeData;

interface AttributeRepositoryInterface
{
    public function all(): iterable;
    public function findOrFail(int $id): object;
    public function nameExists(string $name, ?int $exceptId = null): bool;
    public function create(AttributeData $data): object;
    public function update(int $attributeId, AttributeData $data): object;
    public function isUsed(int $attributeId): bool;
    public function delete(int $attributeId): void;
}
