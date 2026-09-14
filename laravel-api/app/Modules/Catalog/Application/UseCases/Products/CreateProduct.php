<?php
namespace App\Modules\Catalog\Application\UseCases\Products;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductTypeException;
use Illuminate\Support\Str;
final class CreateProduct
{
 public function __construct(private readonly ProductRepositoryInterface $products) {}
 public function execute(ProductData $data): object
 {
  if (!in_array($data->type,['simple','variable'],true)) throw InvalidProductTypeException::unsupported($data->type);
  $slug=Str::slug($data->slug ?: $data->name);
  if($this->products->slugExists($slug)) throw new DuplicateSlugException($slug);
  return $this->products->create($data,$slug);
 }
}
