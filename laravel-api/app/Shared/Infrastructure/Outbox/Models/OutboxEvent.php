<?php

namespace App\Shared\Infrastructure\Outbox\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'event_type', 'deduplication_key', 'status',
        'attempt_count', 'payload', 'last_error', 'next_attempt_at', 'lease_until', 'claim_token', 'dispatched_at',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'payload' => 'array', 'next_attempt_at' => 'datetime', 'lease_until' => 'datetime', 'dispatched_at' => 'datetime'];
    }
}
