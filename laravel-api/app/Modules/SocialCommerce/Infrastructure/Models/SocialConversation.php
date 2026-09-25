<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class SocialConversation extends Model
{
    protected $table = 'social_conversations';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
