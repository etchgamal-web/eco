<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
final class ListPublicProductVariants
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}
    public function execute(int $productId): iterable { return $this->products->publicVariants($productId); }
}
