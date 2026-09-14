<?php

namespace App\Modules\Promotion\Infrastructure\Persistence;

use App\Models\Coupon;
use App\Modules\Promotion\Domain\Contracts\CouponServiceInterface;
use App\Modules\Promotion\Domain\Exceptions\CouponInvalidException;

final class EloquentCouponService implements CouponServiceInterface
{
    public function apply(?string $code, int $userId, int $subtotal): array
    {
        if ($code === null || trim($code) === '') {
            return ['code' => null, 'discount' => 0];
        }
        $normalized = strtoupper(trim($code));
        // Checkout invokes this service inside its database transaction. Locking the
        // coupon row serializes usage-limit checks with the subsequent usage insert.
        $coupon = Coupon::query()->where('code', $normalized)->where('is_active', true)->lockForUpdate()->first();
        $now = now();
        if ($coupon === null || ($coupon->starts_at && $coupon->starts_at->isFuture()) || ($coupon->ends_at && $coupon->ends_at->isPast())) {
            throw CouponInvalidException::forCode($normalized);
        }
        if ($subtotal < $coupon->minimum_order_amount || ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) || ($coupon->per_user_limit !== null && $coupon->usages()->where('user_id', $userId)->count() >= $coupon->per_user_limit)) {
            throw CouponInvalidException::forCode($normalized);
        }
        $discount = $coupon->type === 'percent'
            ? (int) round($subtotal * min(100, $coupon->value) / 100)
            : min($subtotal, $coupon->value);
        return ['code' => $coupon->code, 'discount' => $discount];
    }
}
