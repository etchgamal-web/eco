<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface SocialConnectionRepositoryInterface
{
    public function list(array $filters = []): array;

    public function find(int $id): object;

    public function create(array $data): object;

    public function update(object $connection, array $data): object;

    public function delete(object $connection): void;

    public function activeForChannel(string $channel): ?object;
}
