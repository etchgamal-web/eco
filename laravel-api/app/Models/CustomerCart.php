<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CustomerCart extends Model {
 protected $fillable=['user_id','last_activity_at','abandoned_at','recovered_at','recovery_token','recovery_reminder_count'];
 protected $appends=['totals'];
 protected function casts(): array{return ['last_activity_at'=>'datetime','abandoned_at'=>'datetime','recovered_at'=>'datetime','recovery_reminder_count'=>'integer'];}
 public function user(): BelongsTo{return $this->belongsTo(User::class);}
 public function items(): HasMany{return $this->hasMany(CustomerCartItem::class,'cart_id')->with(['product','variant']);}
 public function getTotalsAttribute(): array{$subtotal=$this->items->sum(function($item):int{return (($item->variant?->price??$item->product?->price)??0)*$item->quantity;});return ['subtotal'=>$subtotal,'discount'=>0,'tax'=>0,'shipping'=>0,'total'=>$subtotal,'currency'=>'EGP'];}
}
