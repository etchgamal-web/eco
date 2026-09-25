<?php

namespace App\Modules\AI\Infrastructure\Models;


use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['input' => 'array', 'output' => 'array'];
    }
}
