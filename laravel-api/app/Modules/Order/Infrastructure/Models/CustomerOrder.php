<?php

namespace App\Modules\Order\Infrastructure\Models;
use App\Modules\Order\Infrastructure\Models\CustomerOrderItem;
use App\Modules\Order\Infrastructure\Models\OrderActivity;
use App\Modules\Order\Infrastructure\Models\OrderReview;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use App\Modules\Order\Infrastructure\Models\OrderReturn;

class CustomerOrder extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'guest_email', 'guest_phone', 'status', 'total_amount', 'subtotal_amount', 'discount_amount', 'coupon_code',
        'tax_amount', 'tax_rate', 'tax_rule_id', 'shipping_amount', 'shipping_cost', 'shipping_subsidy', 'currency', 'shipping_address', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer', 'subtotal_amount' => 'integer',
            'discount_amount' => 'integer', 'tax_amount' => 'integer',
            'shipping_amount' => 'integer', 'shipping_cost' => 'integer', 'shipping_subsidy' => 'integer', 'tax_rate' => 'decimal:4', 'tax_rule_id' => 'integer', 'shipping_address' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class, 'order_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class, 'order_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'order_id');
    }
}
