<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;
final class InventoryItem extends Model{protected $fillable=['product_id','variant_id','on_hand','reserved'];protected $appends=['available'];protected function casts():array{return ['on_hand'=>'integer','reserved'=>'integer'];}public function product():BelongsTo{return $this->belongsTo(Product::class);}public function variant():BelongsTo{return $this->belongsTo(ProductVariant::class,'variant_id');}public function movements():HasMany{return $this->hasMany(InventoryMovement::class);}public function getAvailableAttribute():int{return $this->on_hand-$this->reserved;}}
