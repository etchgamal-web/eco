<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Models\SocialMessageTemplate;
use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;

final class EloquentMessageTemplateRepository implements MessageTemplateRepositoryInterface
{
    public function create(array $data): object
    {
        return SocialMessageTemplate::query()->create($data);
    }

    public function find(int $id): object
    {
        return SocialMessageTemplate::query()->findOrFail($id);
    }

    public function update(object $template, array $data): object
    {
        $template->fill($data);
        $template->save();
        return $template;
    }

    public function delete(object $template): void
    {
        $template->delete();
    }

    public function list(array $filters = []): array
    {
        return SocialMessageTemplate::query()
            ->when(isset($filters['channel']), fn ($query) => $query->where('channel', $filters['channel']))
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->latest()
            ->get()
            ->all();
    }
}
