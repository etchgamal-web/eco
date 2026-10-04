<?php

namespace App\Modules\Staff\Infrastructure\Persistence;

use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
use App\Modules\Staff\Infrastructure\Models\AuditLog;
use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function record(?object $actor, string $action, string $targetType, ?int $targetId, array $metadata = []): void
    {
        AuditLog::query()->create(['actor_id' => $actor?->id, 'action' => $action, 'target_type' => $targetType, 'target_id' => $targetId, 'metadata' => $metadata]);
    }

    public function listForStaff(int $targetId, array $filters): array
    {
        return $this->listForTarget(User::class, $targetId, $filters);
    }

    public function listForRole(int $targetId, array $filters): array
    {
        return $this->listForTarget(Role::class, $targetId, $filters);
    }

    /** @return array{items: array<int, mixed>, meta: array<string, int>} */
    private function listForTarget(string $targetType, int $targetId, array $filters): array
    {
        $result = AuditLog::query()->with('actor')->where('target_type', $targetType)->where('target_id', $targetId)
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', '%'.$action.'%'))
            ->when($filters['actor_id'] ?? null, fn ($query, $actorId) => $query->where('actor_id', $actorId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1));

        return ['items' => $result->items(), 'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => $result->perPage(), 'total' => $result->total()]];
    }
}
