<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
