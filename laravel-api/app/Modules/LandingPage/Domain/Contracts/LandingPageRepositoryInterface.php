<?php

namespace App\Modules\LandingPage\Domain\Contracts;

interface LandingPageRepositoryInterface
{
    public function list(array $filters = []): array;

    public function find(int $id): object;

    public function bySlug(string $slug, bool $publishedOnly = true): object;

    public function save(array $data, ?int $id = null): object;

    public function delete(object $page): void;

    public function addLead(int $pageId, array $data): object;

    public function listLeads(array $filters = []): array;

    public function updateLead(int $id, array $data): object;

    public function recordEvent(int $pageId, array $data): object;

    public function stats(int $pageId, array $filters = []): array;
}
