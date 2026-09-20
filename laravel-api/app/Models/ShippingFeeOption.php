<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingFeeOption extends Model
{
    protected $fillable = ['shipping_pricing_plan_id', 'code', 'name', 'fee_type', 'calculation_basis', 'amount', 'applies_to', 'trigger_conditions', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean', 'trigger_conditions' => 'array'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ShippingPricingPlan::class, 'shipping_pricing_plan_id');
    }
}
