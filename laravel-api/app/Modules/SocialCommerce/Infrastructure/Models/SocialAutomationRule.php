<?php

namespace App\Modules\SocialCommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAutomationRule extends Model
{
    protected $table = 'social_automation_rules';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'actions' => 'array', 'is_active' => 'boolean'];
    }
}
