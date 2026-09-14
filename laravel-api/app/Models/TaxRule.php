<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaxRule extends Model
{
    protected $fillable = ['name', 'country', 'state', 'rate', 'is_active'];
    protected function casts(): array { return ['rate' => 'decimal:4', 'is_active' => 'boolean']; }
}
