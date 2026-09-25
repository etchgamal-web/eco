<?php

namespace App\Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderCircuitBreaker extends Model
{
    protected $fillable = ['provider', 'failure_count', 'opened_until', 'last_failure_at'];

    protected function casts(): array
    {
        return ['failure_count' => 'integer', 'opened_until' => 'datetime', 'last_failure_at' => 'datetime'];
    }
}
