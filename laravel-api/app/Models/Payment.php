<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'method', 'provider_reference', 'amount',
        'currency', 'status', 'idempotency_key', 'metadata',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function operations(): HasMany
    {
        return $this->hasMany(PaymentOperation::class);
    }
}
