<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShippingSettlement extends Model
{
    protected $fillable = ['shipping_provider_id', 'reference', 'period_from', 'period_to', 'status', 'currency', 'shipments_count', 'expected_total', 'actual_total', 'difference_total', 'metadata'];
    protected function casts(): array { return ['period_from' => 'date', 'period_to' => 'date', 'shipments_count' => 'integer', 'expected_total' => 'integer', 'actual_total' => 'integer', 'difference_total' => 'integer', 'metadata' => 'array']; }
    public function provider(): BelongsTo { return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id'); }
    public function items(): HasMany { return $this->hasMany(ShippingSettlementItem::class); }
}
