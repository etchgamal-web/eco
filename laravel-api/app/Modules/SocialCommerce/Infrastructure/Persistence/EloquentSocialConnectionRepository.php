<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Models\SocialConnection;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;

final class EloquentSocialConnectionRepository implements SocialConnectionRepositoryInterface
{
    public function list(array $filters = []): array
    {
        $q = SocialConnection::query();
        if (isset($filters['channel'])) {
            $q->where('channel', $filters['channel']);
        }

        return $q->latest()->get()->all();
    }

    public function find(int $id): object
    {
        return SocialConnection::query()->findOrFail($id);
    }

    public function create(array $data): object
    {
        return SocialConnection::query()->create($data);
    }

    public function update(object $connection, array $data): object
    {
        $connection->fill(array_filter($data, fn ($v) => $v !== null));
        $connection->save();

        return $connection;
    }

    public function delete(object $connection): void
    {
        $connection->delete();
    }

    public function activeForChannel(string $channel): ?object
    {
        return SocialConnection::query()->where('channel', $channel)->where('is_active', true)->latest()->first();
    }
}
