<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerCartItem extends Model { protected $fillable=['cart_id','product_id','variant_id','quantity']; protected function casts(): array{return ['quantity'=>'integer'];} public function cart(): BelongsTo{return $this->belongsTo(CustomerCart::class,'cart_id');} public function product(): BelongsTo{return $this->belongsTo(Product::class);} public function variant(): BelongsTo{return $this->belongsTo(ProductVariant::class,'variant_id');} }
