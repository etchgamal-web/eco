<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Models\CustomerWishlist;
use App\Modules\Customer\Domain\Contracts\WishlistRepositoryInterface;

final class EloquentWishlistRepository implements WishlistRepositoryInterface
{
    public function listForUser(int $userId): iterable
    {
        return CustomerWishlist::query()->with('product')->where('user_id', $userId)->latest()->get();
    }

    public function add(int $userId, int $productId): object
    {
        return CustomerWishlist::query()->firstOrCreate(['user_id' => $userId, 'product_id' => $productId]);
    }

    public function remove(int $userId, int $productId): void
    {
        CustomerWishlist::query()->where('user_id', $userId)->where('product_id', $productId)->delete();
    }
}
