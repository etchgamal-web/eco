<?php

namespace App\Modules\Order\Infrastructure\Models;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Auth\Infrastructure\Models\User;

class OrderReview extends Model
{
    protected $fillable = [
        'order_id', 'reviewer_id', 'started_at', 'contacted_at', 'contact_result',
        'notes', 'confirmed_at', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'contacted_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

