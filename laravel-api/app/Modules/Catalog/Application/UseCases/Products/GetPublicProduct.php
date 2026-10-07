<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
final class GetPublicProduct
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}
    public function execute(string|int $id): object { return $this->products->findPublicOrFail($id); }
}
