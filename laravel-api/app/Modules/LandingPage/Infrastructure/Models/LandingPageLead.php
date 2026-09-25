<?php

namespace App\Modules\LandingPage\Infrastructure\Models;
use App\Modules\LandingPage\Infrastructure\Models\LandingPage;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageLead extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'contacted_at' => 'datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }
}
