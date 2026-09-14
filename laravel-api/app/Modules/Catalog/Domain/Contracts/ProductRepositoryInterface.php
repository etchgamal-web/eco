<?php

namespace App\Modules\Catalog\Domain\Contracts;

use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\ValueObjects\ProductListCriteria;
use App\Modules\Catalog\Domain\ValueObjects\ProductVariantData;

interface ProductRepositoryInterface
{
    public function all(): iterable;
    public function search(ProductListCriteria $criteria): object;
    public function findOrFail(int $id): object;
    public function slugExists(string $slug, ?int $exceptId = null): bool;
    public function create(ProductData $data, string $slug): object;
    public function update(int $productId, ProductData $data, string $slug): object;
    public function delete(int $productId): void;
    public function hasVariants(int $productId): bool;
    public function variants(int $productId): iterable;
    public function findVariantOrFail(int $productId, int $variantId): object;
    public function skuExists(string $sku, ?int $exceptId = null): bool;
    public function combinationExists(int $productId, string $hash, ?int $exceptId = null): bool;
    public function createVariant(int $productId, ProductVariantData $data, string $hash, iterable $values): object;
    public function updateVariant(int $variantId, ProductVariantData $data, string $hash, iterable $values): object;
    public function deleteVariant(int $variantId): void;
}
