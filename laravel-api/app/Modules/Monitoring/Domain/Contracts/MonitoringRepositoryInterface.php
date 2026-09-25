<?php

namespace App\Modules\Monitoring\Domain\Contracts;

interface MonitoringRepositoryInterface
{
    public function settings(): array;

    public function saveSetting(string $type, int $days, bool $enabled): array;

    public function delayed(array $filters): array;

    public function alerts(array $filters): array;

    public function alert(int $id): mixed;

    public function detect(): array;

    public function findAlert(int $id): mixed;

    public function acknowledge(int $id, int $userId): mixed;

    public function resolve(int $id, int $userId): mixed;

    public function bulkAcknowledge(array $ids, int $userId, ?string $reason = null): array;

    public function bulkResolve(array $ids, int $userId, ?string $reason = null): array;
}
