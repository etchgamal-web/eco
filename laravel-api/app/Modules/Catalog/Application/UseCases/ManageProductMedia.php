<?php
namespace App\Modules\Catalog\Application\UseCases;
use App\Modules\Catalog\Domain\Contracts\ProductMediaRepositoryInterface;
final class ManageProductMedia
{
    public function __construct(private readonly ProductMediaRepositoryInterface $media) {}
    public function list(int $productId, ?int $variantId = null): iterable { return $variantId === null ? $this->media->listForProduct($productId) : $this->media->listForVariant($productId, $variantId); }
    public function upload(int $productId, ?int $variantId, object $file): object { return $this->media->store($productId, $variantId, $file); }
    public function remove(int $productId, ?int $variantId, int $mediaId): void { $variantId === null ? $this->media->remove($productId, $mediaId) : $this->media->removeVariant($productId, $variantId, $mediaId); }
    public function reorder(int $productId, ?int $variantId, int $mediaId, int $sortOrder): object { return $this->media->reorder($productId, $variantId, $mediaId, $sortOrder); }
}
