<?php
namespace App\Modules\Catalog\Infrastructure\Persistence;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Modules\Catalog\Domain\Contracts\ProductReviewRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\ReviewNotAllowedException;
final class EloquentProductReviewRepository implements ProductReviewRepositoryInterface
{
    public function approvedForProduct(int $productId): iterable { Product::query()->findOrFail($productId); return ProductReview::query()->with('user:id,name')->where('product_id', $productId)->where('status', 'approved')->latest()->get(); }
    public function summary(int $productId): array { $query = ProductReview::query()->where('product_id', $productId)->where('status', 'approved'); return ['count' => (int) $query->count(), 'average_rating' => round((float) ($query->avg('rating') ?? 0), 2)]; }
    public function createForVerifiedCustomer(int $userId, int $productId, array $data): object
    {
        Product::query()->findOrFail($productId);
        if (ProductReview::query()->where('product_id', $productId)->where('user_id', $userId)->exists()) throw ReviewNotAllowedException::alreadyReviewed();
        $purchased = CustomerOrder::query()->where('user_id', $userId)->where('status', 'delivered')->whereHas('items', fn ($q) => $q->where('product_id', $productId))->exists();
        if (! $purchased) throw ReviewNotAllowedException::notPurchased();
        return ProductReview::query()->create(['product_id' => $productId, 'variant_id' => $data['variant_id'] ?? null, 'user_id' => $userId, 'rating' => $data['rating'], 'title' => $data['title'] ?? null, 'body' => $data['body'] ?? null, 'status' => 'pending', 'verified_purchase' => true]);
    }
    public function all(): iterable { return ProductReview::query()->with(['product:id,name', 'user:id,name'])->latest()->get(); }
    public function moderate(int $reviewId, string $status): object { if (! in_array($status, ['approved', 'rejected'], true)) throw ReviewNotAllowedException::invalidStatus(); $review = ProductReview::query()->findOrFail($reviewId); $review->update(['status' => $status]); return $review->fresh(['product:id,name', 'user:id,name']); }
}
