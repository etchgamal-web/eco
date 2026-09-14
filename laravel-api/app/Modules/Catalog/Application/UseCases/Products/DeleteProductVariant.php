<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
final class DeleteProductVariant
{
 public function __construct(private readonly ProductRepositoryInterface $products) {}
 public function execute(int $productId,int $variantId): void {$this->products->findVariantOrFail($productId,$variantId);$this->products->deleteVariant($variantId);}
}
