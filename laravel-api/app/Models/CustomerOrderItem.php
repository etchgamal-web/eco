<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerOrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'name', 'sku', 'quantity',
        'unit_price', 'discount_amount', 'tax_amount', 'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer', 'unit_price' => 'integer',
            'discount_amount' => 'integer', 'tax_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(CustomerOrder::class, 'order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
