<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = ['code', 'name', 'carrier', 'base_fee', 'currency', 'is_active'];

    protected function casts(): array
    {
        return ['base_fee' => 'integer', 'is_active' => 'boolean'];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
