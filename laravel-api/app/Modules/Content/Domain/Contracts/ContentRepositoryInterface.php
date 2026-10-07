<?php

namespace App\Modules\Content\Domain\Contracts;

interface ContentRepositoryInterface
{
    /** @return array{data: list<object>, meta: array{current_page: int, per_page: int, total: int, last_page: int}} */
    public function listPublic(?string $type = null, int $page = 1, int $perPage = 20): array;
    public function findPublicBySlug(string $slug): object;
    public function listAdmin(array $filters = []): iterable;
    public function find(int $id): object;
    public function save(array $data, ?int $id = null): object;
    public function delete(int $id): void;
    public function publish(int $id): object;
    public function unpublish(int $id): object;
}
