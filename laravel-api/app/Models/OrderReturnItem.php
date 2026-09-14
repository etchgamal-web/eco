<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class OrderReturnItem extends Model
{
    protected $table = 'order_return_items';
    protected $fillable = ['return_id','order_item_id','product_id','quantity','unit_price'];
    protected function casts(): array { return ['quantity' => 'integer', 'unit_price' => 'integer']; }
    public function returnRequest(): BelongsTo { return $this->belongsTo(OrderReturn::class, 'return_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(CustomerOrderItem::class, 'order_item_id'); }
}
