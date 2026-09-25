<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SocialCommerce\Infrastructure\Persistence\Concerns\SocialResponderAttributes;

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
