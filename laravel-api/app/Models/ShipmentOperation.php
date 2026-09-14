<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShipmentOperation extends Model
{
    protected $fillable = [
        'shipment_id', 'operation', 'status', 'idempotency_key', 'provider_reference',
        'attempt_count', 'request_payload', 'response_payload', 'last_error', 'next_retry_at',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'request_payload' => 'array', 'response_payload' => 'array', 'next_retry_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
