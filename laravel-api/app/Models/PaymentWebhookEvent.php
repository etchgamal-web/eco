<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'status',
        'payment_reference',
        'payload',
        'processing_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
