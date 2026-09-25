<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'shipping_method_id', 'method_code', 'provider_code', 'weight', 'item_quantity', 'zone_code', 'tracking_number',
        'fee', 'currency', 'status', 'creation_status', 'creation_error', 'created_at_provider', 'public_tracking_token',
        'address_snapshot', 'idempotency_key', 'metadata',
    ];

    protected function casts(): array
    {
        return ['fee' => 'integer', 'weight' => 'decimal:3', 'item_quantity' => 'integer', 'address_snapshot' => 'array', 'metadata' => 'array', 'created_at_provider' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ShipmentOperation::class);
    }

    public function pricingSnapshot(): HasOne
    {
        return $this->hasOne(ShipmentPricingSnapshot::class);
    }
}
