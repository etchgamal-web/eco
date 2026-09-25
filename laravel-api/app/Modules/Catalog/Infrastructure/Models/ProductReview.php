<?php
namespace App\Modules\Catalog\Infrastructure\Models;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductVariant;
use App\Modules\Auth\Infrastructure\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductReview extends Model
{
    protected $fillable = ['product_id','variant_id','user_id','rating','title','body','status','verified_purchase'];
    protected function casts(): array { return ['rating' => 'integer', 'verified_purchase' => 'boolean']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
