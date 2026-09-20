<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingSettlementItem extends Model
{
    protected $fillable = ['shipping_settlement_id', 'shipment_id', 'expected_total', 'actual_total', 'difference', 'status', 'expected_charges', 'actual_charges', 'metadata'];
    protected function casts(): array { return ['expected_total' => 'integer', 'actual_total' => 'integer', 'difference' => 'integer', 'expected_charges' => 'array', 'actual_charges' => 'array', 'metadata' => 'array']; }
    public function settlement(): BelongsTo { return $this->belongsTo(ShippingSettlement::class, 'shipping_settlement_id'); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
