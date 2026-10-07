<?php

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;

final class GetPublicProductVariant
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(int $productId, int $variantId): object
    {
        return $this->products->findPublicVariantOrFail($productId, $variantId);
    }
}
