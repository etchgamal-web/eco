<?php
namespace App\Modules\Catalog\Infrastructure\Persistence;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\ValueObjects\ProductListCriteria;
use App\Modules\Catalog\Domain\ValueObjects\ProductVariantData;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\DuplicateSkuException;
use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;
use App\Modules\Catalog\Domain\Exceptions\InvalidVariantCombinationException;
use App\Modules\Catalog\Domain\Exceptions\ProductNotFoundException;
use App\Modules\Catalog\Domain\Exceptions\VariantNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class EloquentProductRepository implements ProductRepositoryInterface
{
    public function all(): iterable { return Product::query()->with(['brand', 'category'])->orderByDesc('id')->get(); }
    public function search(ProductListCriteria $criteria): object
    {
        $query = Product::query()->with(['brand', 'category'])
            ->when($criteria->search, fn ($q, $search) => $q->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', '%' . $search . '%')->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', '%' . $search . '%'));
            }))
            ->when($criteria->categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->when($criteria->brandId, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($criteria->type, fn ($q, $type) => $q->where('type', $type))
            ->when($criteria->status, fn ($q, $status) => $q->where('status', $status))
            ->when($criteria->minPrice !== null, fn ($q) => $q->where('price', '>=', $criteria->minPrice))
            ->when($criteria->maxPrice !== null, fn ($q) => $q->where('price', '<=', $criteria->maxPrice));

        match ($criteria->sort) {
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        return $query->paginate($criteria->perPage, ['*'], 'page', $criteria->page);
    }
    public function findOrFail(int $id): object
    {
        $model = Product::query()->with(['brand', 'category', 'variants.attributeValues.attribute'])->find($id);
        if ($model === null) throw new ProductNotFoundException($id);
        return $model;
    }
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return Product::query()->where('slug', $slug)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
    }
    public function create(ProductData $data, string $slug): object
    {
        try { return Product::query()->create(array_merge($data->toArray(), ['slug' => $slug]))->load(['brand', 'category']); }
        catch (QueryException $e) { if ($this->slugExists($slug)) throw new DuplicateSlugException($slug); throw $e; }
    }
    public function update(int $productId, ProductData $data, string $slug): object
    {
        $product = $this->findOrFail($productId);
        try { $product->update(array_merge($data->toArray(), ['slug' => $slug])); return $product->refresh()->load(['brand', 'category', 'variants.attributeValues.attribute']); }
        catch (QueryException $e) { if ($this->slugExists($slug, $product->id)) throw new DuplicateSlugException($slug); throw $e; }
    }
    public function delete(int $productId): void { $this->findOrFail($productId)->delete(); }
    public function hasVariants(int $productId): bool { return $this->findOrFail($productId)->variants()->exists(); }
    public function variants(int $productId): iterable { return $this->findOrFail($productId)->variants()->with('attributeValues.attribute')->orderBy('id')->get(); }
    public function findVariantOrFail(int $productId, int $variantId): object
    {
        $product = $this->findOrFail($productId);
        $model = $product->variants()->with('attributeValues.attribute')->find($variantId);
        if ($model === null) throw new VariantNotFoundException($variantId);
        return $model;
    }
    public function skuExists(string $sku, ?int $exceptId = null): bool
    {
        return ProductVariant::query()->where('sku', $sku)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
    }
    public function combinationExists(int $productId, string $hash, ?int $exceptId = null): bool
    {
        return $this->findOrFail($productId)->variants()->where('combination_hash', $hash)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
    }
    public function createVariant(int $productId, ProductVariantData $data, string $hash, iterable $values): object
    {
        $product = $this->findOrFail($productId);
        $values = collect($values);
        try {
            return DB::transaction(function () use ($product, $data, $hash, $values) {
                $variant = $product->variants()->create(array_merge($data->persistenceData(), ['combination_hash' => $hash]));
                $variant->attributeValues()->attach($this->pivotData($values));
                return $variant->load('attributeValues.attribute');
            });
        } catch (QueryException $e) { $this->throwVariantConflict($product, $data->sku, $hash); throw $e; }
    }
    public function updateVariant(int $variantId, ProductVariantData $data, string $hash, iterable $values): object
    {
        $variant = ProductVariant::query()->findOrFail($variantId);
        $values = collect($values);
        try {
            return DB::transaction(function () use ($variant, $data, $hash, $values) {
                $variant->update(array_merge($data->persistenceData(), ['combination_hash' => $hash]));
                $variant->attributeValues()->sync($this->pivotData($values));
                return $variant->refresh()->load('attributeValues.attribute');
            });
        } catch (QueryException $e) { $this->throwVariantConflict($variant->product, $data->sku, $hash, $variant->id); throw $e; }
    }
    public function deleteVariant(int $variantId): void { ProductVariant::query()->findOrFail($variantId)->delete(); }
    private function pivotData(Collection $values): array
    {
        return $values->mapWithKeys(fn ($value) => [$value->id => ['attribute_id' => $value->attribute_id]])->all();
    }
    private function throwVariantConflict(object $product, string $sku, string $hash, ?int $exceptId = null): void
    {
        if ($this->skuExists($sku, $exceptId)) throw new DuplicateSkuException($sku);
        if ($this->combinationExists($product, $hash, $exceptId)) throw InvalidVariantCombinationException::duplicate();
    }
}
