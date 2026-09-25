<?php

namespace App\Modules\Catalog\Infrastructure\Models;
use App\Modules\Catalog\Infrastructure\Models\Product;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'status'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
