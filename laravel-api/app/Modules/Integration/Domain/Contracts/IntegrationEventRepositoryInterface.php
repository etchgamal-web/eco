<?php

namespace App\Modules\Integration\Domain\Contracts;

interface IntegrationEventRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function list(array $filters): array;

    /** @return array<string, mixed> */
    public function retry(string $source, int $id): array;
}
