<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Auth\Infrastructure\Models\User;


use Illuminate\Database\Eloquent\Model;
use App\Modules\SocialCommerce\Infrastructure\Persistence\Concerns\SocialResponderAttributes;

class SocialInteraction extends Model
{
    use SocialResponderAttributes;

    protected $table = 'social_interactions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
