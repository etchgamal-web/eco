<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductTypeException;
use Illuminate\Support\Str;
final class UpdateProduct
{
 public function __construct(private readonly ProductRepositoryInterface $products) {}
 public function execute(int $id,ProductData $data): object
 {
  $this->products->findOrFail($id);
  if(!in_array($data->type,['simple','variable'],true)) throw InvalidProductTypeException::unsupported($data->type);
  if($data->type==='simple' && $this->products->hasVariants($id)) throw InvalidProductTypeException::hasVariants();
  $slug=Str::slug($data->slug ?: $data->name);
  if($this->products->slugExists($slug,$id)) throw new DuplicateSlugException($slug);
  return $this->products->update($id,$data,$slug);
 }
}
