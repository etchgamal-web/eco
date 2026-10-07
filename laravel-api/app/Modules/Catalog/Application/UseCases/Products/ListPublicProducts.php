<?php

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\ProductListCriteria;

final class ListPublicProducts
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(?ProductListCriteria $criteria = null): object|iterable
    {
        return $criteria === null ? $this->products->allPublic() : $this->products->searchPublic($criteria);
    }
}
