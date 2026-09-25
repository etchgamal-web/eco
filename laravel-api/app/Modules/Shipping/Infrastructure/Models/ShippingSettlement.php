<?php

namespace App\Modules\Shipping\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingSettlement extends Model
{
    protected $fillable = ['shipping_provider_id', 'reference', 'period_from', 'period_to', 'status', 'currency', 'shipments_count', 'total_rows', 'matched_rows', 'mismatched_rows', 'missing_orders', 'duplicate_rows', 'invalid_rows', 'expected_total', 'actual_total', 'difference_total', 'expected_collection', 'actual_collection', 'collection_difference', 'expected_shipping', 'actual_shipping', 'shipping_difference', 'expected_return_fee', 'actual_return_fee', 'return_difference', 'expected_customer_refund', 'actual_customer_refund', 'customer_refund_difference', 'metadata'];

    protected function casts(): array
    {
        return ['period_from' => 'date', 'period_to' => 'date', 'shipments_count' => 'integer', 'total_rows' => 'integer', 'matched_rows' => 'integer', 'mismatched_rows' => 'integer', 'missing_orders' => 'integer', 'duplicate_rows' => 'integer', 'invalid_rows' => 'integer', 'expected_total' => 'integer', 'actual_total' => 'integer', 'difference_total' => 'integer', 'expected_collection' => 'integer', 'actual_collection' => 'integer', 'collection_difference' => 'integer', 'expected_shipping' => 'integer', 'actual_shipping' => 'integer', 'shipping_difference' => 'integer', 'expected_return_fee' => 'integer', 'actual_return_fee' => 'integer', 'return_difference' => 'integer', 'expected_customer_refund' => 'integer', 'actual_customer_refund' => 'integer', 'customer_refund_difference' => 'integer', 'metadata' => 'array'];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShippingSettlementItem::class);
    }
}
