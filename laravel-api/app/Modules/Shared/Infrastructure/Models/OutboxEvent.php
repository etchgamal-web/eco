<?php

namespace App\Modules\Shared\Infrastructure\Models;


use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'event_type', 'deduplication_key', 'status',
        'attempt_count', 'payload', 'last_error', 'next_attempt_at', 'dispatched_at',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'payload' => 'array', 'next_attempt_at' => 'datetime', 'dispatched_at' => 'datetime'];
    }
}
