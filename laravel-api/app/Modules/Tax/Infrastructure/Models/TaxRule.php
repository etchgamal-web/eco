<?php

namespace App\Modules\Tax\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    protected $fillable = ['name', 'country', 'state', 'rate', 'is_active'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:4', 'is_active' => 'boolean'];
    }
}
