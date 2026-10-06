<?php

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;

final class GetProduct
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(string|int $id): object
    {
        return ctype_digit((string) $id)
            ? $this->products->findOrFail((int) $id)
            : $this->products->findBySlugOrFail((string) $id);
    }
}
