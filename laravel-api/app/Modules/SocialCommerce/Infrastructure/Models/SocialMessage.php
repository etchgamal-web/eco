<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;

use App\Modules\SocialCommerce\Infrastructure\Persistence\Concerns\SocialResponderAttributes;
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
