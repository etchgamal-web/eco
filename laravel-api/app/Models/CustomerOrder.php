<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CustomerOrder extends Model
{
    protected $fillable = [
        'user_id', 'guest_email', 'guest_phone', 'status', 'total_amount', 'subtotal_amount', 'discount_amount', 'coupon_code',
        'tax_amount', 'tax_rate', 'tax_rule_id', 'shipping_amount', 'currency', 'shipping_address', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer', 'subtotal_amount' => 'integer',
            'discount_amount' => 'integer', 'tax_amount' => 'integer',
            'shipping_amount' => 'integer', 'tax_rate' => 'decimal:4', 'tax_rule_id' => 'integer', 'shipping_address' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(CustomerOrderItem::class, 'order_id'); }

    public function payments(): HasMany { return $this->hasMany(Payment::class, 'order_id'); }

    public function shipments(): HasMany { return $this->hasMany(Shipment::class, 'order_id'); }
}
