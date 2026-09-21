<?php
namespace App\Modules\Monitoring\Domain\Contracts;
interface MonitoringRepositoryInterface { public function settings(): array; public function saveSetting(string $type,int $days,bool $enabled): array; public function delayed(array $filters): array; public function detect(): array; public function findAlert(int $id): mixed; public function acknowledge(int $id,int $userId): mixed; public function resolve(int $id,int $userId): mixed; }
