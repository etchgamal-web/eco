<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SocialResponderAttributes
{
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
