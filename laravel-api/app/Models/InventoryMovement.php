<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class InventoryMovement extends Model{protected $fillable=['inventory_item_id','actor_id','quantity','on_hand_after','reason','note'];protected function casts():array{return ['quantity'=>'integer','on_hand_after'=>'integer'];}public function inventoryItem():BelongsTo{return $this->belongsTo(InventoryItem::class);}public function actor():BelongsTo{return $this->belongsTo(User::class,'actor_id');}}
