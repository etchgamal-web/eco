<?php
namespace App\Modules\Catalog\Infrastructure\Persistence;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Modules\Catalog\Domain\Contracts\ProductMediaRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\ProductMediaNotFoundException;
use Illuminate\Support\Facades\Storage;
final class EloquentProductMediaRepository implements ProductMediaRepositoryInterface
{
    public function listForProduct(int $productId): iterable { Product::query()->findOrFail($productId); return ProductMedia::query()->where('product_id', $productId)->whereNull('variant_id')->orderBy('sort_order')->orderBy('id')->get(); }
    public function listForVariant(int $productId, int $variantId): iterable { $this->variant($productId, $variantId); return ProductMedia::query()->where('product_id', $productId)->where('variant_id', $variantId)->orderBy('sort_order')->orderBy('id')->get(); }
    public function store(int $productId, ?int $variantId, object $file): object
    {
        Product::query()->findOrFail($productId);
        if ($variantId !== null) $this->variant($productId, $variantId);
        $directory = $variantId === null ? "products/{$productId}" : "products/{$productId}/variants/{$variantId}";
        $path = $file->store($directory, 'public');
        $query = ProductMedia::query()->where('product_id', $productId)->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId));
        $media = ProductMedia::query()->create(['product_id' => $productId, 'variant_id' => $variantId, 'disk' => 'public', 'path' => $path, 'url' => Storage::disk('public')->url($path), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'sort_order' => (int) $query->max('sort_order') + 1, 'is_primary' => ! $query->exists()]);
        return $media;
    }
    public function remove(int $productId, int $mediaId): void { $this->removeScoped($productId, null, $mediaId); }
    public function removeVariant(int $productId, int $variantId, int $mediaId): void { $this->variant($productId, $variantId); $this->removeScoped($productId, $variantId, $mediaId); }
    public function reorder(int $productId, ?int $variantId, int $mediaId, int $sortOrder): object
    {
        $query = ProductMedia::query()->whereKey($mediaId)->where('product_id', $productId)->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId));
        $media = $query->first();
        if ($media === null) throw new ProductMediaNotFoundException($mediaId);
        $media->update(['sort_order' => $sortOrder]);
        return $media->fresh();
    }
    private function removeScoped(int $productId, ?int $variantId, int $mediaId): void
    {
        $query = ProductMedia::query()->whereKey($mediaId)->where('product_id', $productId)->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId));
        $media = $query->first();
        if ($media === null) throw new ProductMediaNotFoundException($mediaId);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
    private function variant(int $productId, int $variantId): ProductVariant { return ProductVariant::query()->where('product_id', $productId)->findOrFail($variantId); }
}
