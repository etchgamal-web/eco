<?php

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\ValueObjects\BrandData;

interface BrandRepositoryInterface
{
    public function all(): iterable;
    public function findOrFail(int $id): object;
    public function slugExists(string $slug, ?int $exceptId = null): bool;
    public function create(BrandData $data, string $slug): object;
    public function update(int $brandId, BrandData $data, string $slug): object;
    public function hasProducts(int $brandId): bool;
    public function delete(int $brandId): void;
}
