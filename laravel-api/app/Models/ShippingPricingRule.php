<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingPricingRule extends Model
{
    protected $fillable = ['shipping_pricing_plan_id', 'rule_type', 'min_weight', 'max_weight', 'min_quantity', 'max_quantity', 'zone_code', 'base_amount', 'additional_unit_amount', 'sort_order', 'is_active', 'conditions'];
    protected function casts(): array { return ['min_weight' => 'decimal:3', 'max_weight' => 'decimal:3', 'min_quantity' => 'integer', 'max_quantity' => 'integer', 'base_amount' => 'integer', 'additional_unit_amount' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean', 'conditions' => 'array']; }
    public function plan(): BelongsTo { return $this->belongsTo(ShippingPricingPlan::class, 'shipping_pricing_plan_id'); }
}
