<?php
namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\ProductListCriteria;
use Illuminate\Support\Collection;

final class ListProducts
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(?ProductListCriteria $criteria = null): object
    {
        return $criteria === null ? $this->products->all() : $this->products->search($criteria);
    }
}
