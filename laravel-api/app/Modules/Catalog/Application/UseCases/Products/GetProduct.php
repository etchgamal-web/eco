<?php
namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;

final class GetProduct
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(int $id): object
    {
        return $this->products->findOrFail($id);
    }
}
