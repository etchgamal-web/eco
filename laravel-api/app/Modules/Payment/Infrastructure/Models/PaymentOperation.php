<?php

namespace App\Modules\Payment\Infrastructure\Models;
use App\Modules\Payment\Infrastructure\Models\Payment;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOperation extends Model
{
    protected $fillable = [
        'payment_id', 'operation', 'status', 'idempotency_key', 'provider_reference',
        'attempt_count', 'request_payload', 'response_payload', 'last_error', 'next_retry_at',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'request_payload' => 'array', 'response_payload' => 'array', 'next_retry_at' => 'datetime'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
