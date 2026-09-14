<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'minimum_order_amount', 'usage_limit', 'per_user_limit', 'starts_at', 'ends_at', 'is_active'];
    protected function casts(): array { return ['value' => 'integer', 'minimum_order_amount' => 'integer', 'usage_limit' => 'integer', 'per_user_limit' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean']; }
    public function usages(): HasMany { return $this->hasMany(CouponUsage::class); }
}
