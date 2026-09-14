<?php
namespace App\Modules\Catalog\Domain\Contracts;
interface ProductMediaRepositoryInterface
{
    public function listForProduct(int $productId): iterable;
    public function listForVariant(int $productId, int $variantId): iterable;
    public function store(int $productId, ?int $variantId, object $file): object;
    public function remove(int $productId, int $mediaId): void;
    public function removeVariant(int $productId, int $variantId, int $mediaId): void;
    public function reorder(int $productId, ?int $variantId, int $mediaId, int $sortOrder): object;
}
