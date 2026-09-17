<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMessage extends Model
{
    use SocialResponderAttributes;

    protected $table = 'social_messages';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
