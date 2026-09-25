<?php
namespace App\Modules\Customer\Infrastructure\Models;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerWishlist extends Model { protected $fillable=['user_id','product_id']; public function user(): BelongsTo{return $this->belongsTo(User::class);} public function product(): BelongsTo{return $this->belongsTo(Product::class);} }
