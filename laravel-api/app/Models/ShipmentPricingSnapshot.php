<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShipmentPricingSnapshot extends Model
{
    protected $fillable = ['shipment_id', 'shipping_provider_id', 'shipping_pricing_plan_id', 'pricing_method', 'weight', 'item_quantity', 'zone_code', 'currency', 'base_amount', 'applied_fees', 'total_expected_cost', 'calculation_inputs'];
    protected function casts(): array { return ['weight' => 'decimal:3', 'item_quantity' => 'integer', 'base_amount' => 'integer', 'applied_fees' => 'array', 'total_expected_cost' => 'integer', 'calculation_inputs' => 'array']; }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function provider(): BelongsTo { return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id'); }
    public function plan(): BelongsTo { return $this->belongsTo(ShippingPricingPlan::class, 'shipping_pricing_plan_id'); }
    protected static function booted(): void { static::updating(static fn (): bool => false); static::deleting(static fn (): bool => false); }
}
