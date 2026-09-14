<?php

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\ValueObjects\CategoryData;

interface CategoryRepositoryInterface
{
    public function all(): iterable;
    public function findOrFail(int $id): object;
    public function slugExists(string $slug, ?int $exceptId = null): bool;
    public function create(CategoryData $data, string $slug): object;
    public function update(int $categoryId, CategoryData $data, string $slug): object;
    public function wouldCreateCycle(int $categoryId, int $parentId): bool;
    public function hasProductsOrChildren(int $categoryId): bool;
    public function delete(int $categoryId): void;
}
