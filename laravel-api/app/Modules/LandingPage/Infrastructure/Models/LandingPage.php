<?php

namespace App\Modules\LandingPage\Infrastructure\Models;
use App\Modules\LandingPage\Infrastructure\Models\LandingPageLead;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    protected $guarded = [];

    protected $hidden = ['created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['sections' => 'array', 'settings' => 'array', 'published_at' => 'datetime'];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(LandingPageLead::class);
    }
}
