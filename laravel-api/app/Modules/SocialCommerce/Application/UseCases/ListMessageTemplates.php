<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Models\SocialMessageTemplate;

final class ListMessageTemplates
{
    public function execute(array $filters = []): array
    {
        return SocialMessageTemplate::query()
            ->when(isset($filters['channel']), fn ($query) => $query->where('channel', $filters['channel']))
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->latest()
            ->get()
            ->all();
    }
}
