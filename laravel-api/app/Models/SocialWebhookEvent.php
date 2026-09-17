<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialWebhookEvent extends Model
{
    protected $table = 'social_webhook_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
