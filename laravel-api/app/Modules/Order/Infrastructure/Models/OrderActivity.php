<?php

namespace App\Modules\Order\Infrastructure\Models;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Auth\Infrastructure\Models\User;

class OrderActivity extends Model
{
    protected $fillable = [
        'order_id', 'event', 'actor_id', 'source', 'from_status', 'to_status', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
