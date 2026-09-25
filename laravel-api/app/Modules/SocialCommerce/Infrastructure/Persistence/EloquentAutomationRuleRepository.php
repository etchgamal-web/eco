<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Modules\SocialCommerce\Infrastructure\Models\SocialAutomationExecution;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialAutomationRule;
use App\Modules\SocialCommerce\Domain\Contracts\AutomationRuleRepositoryInterface;

final class EloquentAutomationRuleRepository implements AutomationRuleRepositoryInterface
{
    public function list(array $filters = []): array
    {
        return SocialAutomationRule::query()
            ->when(isset($filters['channel']), fn ($query) => $query->where('channel', $filters['channel']))
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->latest()->get()->all();
    }

    public function find(int $id): object
    {
        return SocialAutomationRule::query()->findOrFail($id);
    }

    public function create(array $data): object
    {
        return SocialAutomationRule::query()->create($data);
    }

    public function update(object $rule, array $data): object
    {
        $rule->fill($data)->save();

        return $rule;
    }

    public function delete(object $rule): void
    {
        $rule->delete();
    }

    public function activeFor(string $channel): array
    {
        return SocialAutomationRule::query()->where('is_active', true)->where(function ($q) use ($channel) {
            $q->whereNull('channel')->orWhere('channel', $channel);
        })->get()->all();
    }

    public function executionExists(string $key): bool
    {
        return SocialAutomationExecution::query()->where('idempotency_key', $key)->exists();
    }

    public function recordExecution(array $data): object
    {
        return SocialAutomationExecution::query()->create($data);
    }
}
