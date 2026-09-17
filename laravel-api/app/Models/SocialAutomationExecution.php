<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAutomationExecution extends Model
{
    protected $table = 'social_automation_executions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
