<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\ValueObjects\ProductVariantData;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\DuplicateSkuException;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductTypeException;
use App\Modules\Catalog\Domain\Exceptions\InvalidVariantCombinationException;
use Illuminate\Support\Collection;
final class CreateProductVariant
{
 public function __construct(private readonly ProductRepositoryInterface $products,private readonly AttributeValueRepositoryInterface $attributeValues) {}
 public function execute(int $productId,ProductVariantData $data): object
 {
  $product=$this->products->findOrFail($productId);
  if($product->type!=='variable') throw InvalidProductTypeException::variantNotAllowed();
  if($this->products->skuExists($data->sku)) throw new DuplicateSkuException($data->sku);
  $values=$this->validatedValues($data);$hash=$this->hash($values);
  if($this->products->combinationExists($productId,$hash)) throw InvalidVariantCombinationException::duplicate();
  return $this->products->createVariant($productId,$data,$hash,$values);
 }
 private function validatedValues(ProductVariantData $data): Collection
 {
  $ids=array_values(array_unique($data->attributeValueIds));$values=collect($this->attributeValues->findMany($ids));
  if(count($ids)!==count($data->attributeValueIds)||$values->count()!==count($ids)||$values->pluck('attribute_id')->unique()->count()!==$values->count()) throw InvalidVariantCombinationException::invalid();
  return $values;
 }
 private function hash(Collection $values): string {return hash('sha256',$values->pluck('id')->sort()->implode(':'));}
}
