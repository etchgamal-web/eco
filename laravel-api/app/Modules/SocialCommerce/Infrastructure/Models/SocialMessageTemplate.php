<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMessageTemplate extends Model
{
    protected $table = 'social_message_templates';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_active' => 'boolean'];
    }
}
