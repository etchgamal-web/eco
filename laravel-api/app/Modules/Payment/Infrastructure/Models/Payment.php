<?php

namespace App\Modules\Payment\Infrastructure\Models;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $hidden = ['metadata', 'idempotency_key'];

    protected $appends = ['checkout_url'];

    protected $fillable = [
        'order_id', 'user_id', 'method', 'provider_reference', 'amount',
        'currency', 'status', 'idempotency_key', 'metadata',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array'];
    }

    public function getCheckoutUrlAttribute(): ?string
    {
        $metadata = (array) $this->metadata;
        $url = $metadata['checkout_url'] ?? $metadata['session_url'] ?? null;

        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
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
