<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class OrderReturn extends Model
{
    protected $table = 'order_returns';
    protected $fillable = ['order_id','user_id','status','reason','notes','refund_amount','rejection_reason'];
    protected function casts(): array { return ['refund_amount' => 'integer']; }
    public function order(): BelongsTo { return $this->belongsTo(CustomerOrder::class, 'order_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderReturnItem::class, 'return_id'); }
}
