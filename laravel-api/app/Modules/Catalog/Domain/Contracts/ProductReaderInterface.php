<?php

namespace App\Modules\Catalog\Domain\Contracts;

interface ProductReaderInterface
{
    public function findForCheckout(int $productId): ?object;

    public function findVariantForProduct(int $productId, int $variantId): ?object;
}
