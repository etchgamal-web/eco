<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;


use Illuminate\Database\Eloquent\Model;

class SocialConnection extends Model
{
    protected $table = 'social_connections';

    protected $guarded = [];

    protected $hidden = ['access_token', 'webhook_secret'];

    protected function casts(): array
    {
        return ['access_token' => 'encrypted', 'webhook_secret' => 'encrypted', 'metadata' => 'array', 'is_active' => 'boolean'];
    }
}
