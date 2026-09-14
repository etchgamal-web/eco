<?php
namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;

final class GetProductVariant
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(int $productId, int $variantId): object
    {
        $this->products->findOrFail($productId);

        return $this->products->findVariantOrFail($productId, $variantId);
    }
}
