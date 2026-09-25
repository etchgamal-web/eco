<?php

namespace App\Modules\Shipping\Infrastructure\Models;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use App\Modules\Shipping\Infrastructure\Models\ShippingSettlement;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingSettlementItem extends Model
{
    protected $fillable = ['shipping_settlement_id', 'shipment_id', 'order_number', 'expected_order_amount', 'actual_order_amount', 'order_amount_difference', 'expected_collection', 'actual_collection', 'collection_difference', 'expected_shipping_cost', 'actual_shipping_cost', 'shipping_difference', 'expected_return_fee', 'actual_return_fee', 'return_difference', 'expected_customer_refund', 'actual_customer_refund', 'customer_refund_difference', 'expected_total', 'actual_total', 'difference', 'status', 'expected_charges', 'actual_charges', 'metadata'];
    protected function casts(): array { return ['expected_order_amount' => 'integer', 'actual_order_amount' => 'integer', 'order_amount_difference' => 'integer', 'expected_collection' => 'integer', 'actual_collection' => 'integer', 'collection_difference' => 'integer', 'expected_shipping_cost' => 'integer', 'actual_shipping_cost' => 'integer', 'shipping_difference' => 'integer', 'expected_return_fee' => 'integer', 'actual_return_fee' => 'integer', 'return_difference' => 'integer', 'expected_customer_refund' => 'integer', 'actual_customer_refund' => 'integer', 'customer_refund_difference' => 'integer', 'expected_total' => 'integer', 'actual_total' => 'integer', 'difference' => 'integer', 'expected_charges' => 'array', 'actual_charges' => 'array', 'metadata' => 'array']; }
    public function settlement(): BelongsTo { return $this->belongsTo(ShippingSettlement::class, 'shipping_settlement_id'); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
