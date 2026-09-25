<?php

namespace App\Modules\Shipping\Infrastructure\Models;
use App\Modules\Shipping\Infrastructure\Models\ShippingFeeOption;
use App\Modules\Shipping\Infrastructure\Models\ShippingPricingRule;
use App\Modules\Shipping\Infrastructure\Models\ShippingProvider;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingPricingPlan extends Model
{
    protected $fillable = ['shipping_provider_id', 'name', 'pricing_method', 'currency', 'is_active', 'effective_from', 'effective_until', 'metadata'];
    protected function casts(): array { return ['is_active' => 'boolean', 'effective_from' => 'datetime', 'effective_until' => 'datetime', 'metadata' => 'array']; }
    public function provider(): BelongsTo { return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id'); }
    public function rules(): HasMany { return $this->hasMany(ShippingPricingRule::class); }
    public function feeOptions(): HasMany { return $this->hasMany(ShippingFeeOption::class); }
}
