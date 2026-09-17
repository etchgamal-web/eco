<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface AutomationRuleRepositoryInterface
{
    public function list(array $filters = []): array;

    public function find(int $id): object;

    public function create(array $data): object;

    public function update(object $rule, array $data): object;

    public function delete(object $rule): void;

    public function activeFor(string $channel): array;

    public function executionExists(string $key): bool;

    public function recordExecution(array $data): object;
}
