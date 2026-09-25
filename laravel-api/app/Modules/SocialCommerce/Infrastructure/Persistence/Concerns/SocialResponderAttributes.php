<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SocialResponderAttributes
{
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
