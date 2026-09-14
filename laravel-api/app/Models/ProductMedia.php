<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class ProductMedia extends Model
{
    protected $table = 'product_media';
    protected $fillable = ['product_id','variant_id','disk','path','url','original_name','mime_type','size','sort_order','is_primary'];
    protected function casts(): array { return ['size' => 'integer', 'sort_order' => 'integer', 'is_primary' => 'boolean']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
