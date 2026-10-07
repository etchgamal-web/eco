<?php

namespace App\Modules\Content\Domain\Contracts;

interface ContentRepositoryInterface
{
    public function listPublic(?string $type = null): iterable;
    public function findPublicBySlug(string $slug): object;
    public function listAdmin(array $filters = []): iterable;
    public function find(int $id): object;
    public function save(array $data, ?int $id = null): object;
    public function delete(int $id): void;
    public function publish(int $id): object;
    public function unpublish(int $id): object;
}
