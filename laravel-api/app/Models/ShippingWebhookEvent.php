<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ShippingWebhookEvent extends Model
{
    protected $fillable = [
        'provider', 'event_id', 'event_type', 'shipment_reference', 'status',
        'payload', 'processing_error', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
