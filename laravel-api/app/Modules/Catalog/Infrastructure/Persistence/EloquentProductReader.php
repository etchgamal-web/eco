<?php

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Domain\Contracts\ProductReaderInterface;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;

final class EloquentProductReader implements ProductReaderInterface
{
    public function findForCheckout(int $productId): ?object
    {
        return Product::query()->with('variants')->find($productId);
    }

    public function findVariantForProduct(int $productId, int $variantId): ?object
    {
        return ProductVariant::query()
            ->where('product_id', $productId)
            ->whereKey($variantId)
            ->first();
    }
}
