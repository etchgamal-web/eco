<?php

namespace App\Modules\Order\Infrastructure\Models;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    protected $table = 'order_returns';

    protected $fillable = ['order_id', 'shipment_id', 'user_id', 'status', 'reason', 'notes', 'refund_amount', 'return_shipping_fee', 'rejection_reason', 'received_at', 'inspected_at', 'inspection_notes'];

    protected function casts(): array
    {
        return ['refund_amount' => 'integer', 'return_shipping_fee' => 'integer', 'received_at' => 'datetime', 'inspected_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'return_id');
    }
}
