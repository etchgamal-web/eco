<?php

namespace App\Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOperation extends Model
{
    protected $fillable = [
        'payment_id', 'operation', 'status', 'idempotency_key', 'provider_reference',
        'attempt_count', 'request_payload', 'response_payload', 'last_error', 'next_retry_at', 'last_reconciliation_at', 'next_reconciliation_at', 'lease_token', 'lease_expires_at', 'requested_amount', 'confirmed_amount',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'requested_amount' => 'integer', 'confirmed_amount' => 'integer', 'request_payload' => 'array', 'response_payload' => 'array', 'next_retry_at' => 'datetime', 'last_reconciliation_at' => 'datetime', 'next_reconciliation_at' => 'datetime', 'lease_expires_at' => 'datetime'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
