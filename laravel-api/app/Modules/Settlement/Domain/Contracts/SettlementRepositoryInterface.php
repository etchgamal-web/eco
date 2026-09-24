<?php
namespace App\Modules\Settlement\Domain\Contracts;
interface SettlementRepositoryInterface { public function settings(): array; public function saveSetting(string $key,mixed $value): array; public function import(string $path,string $providerCode,?string $periodFrom,?string $periodTo): mixed; public function paginate(array $filters = []): mixed; public function summary(array $filters = []): array; public function providers(array $filters = []): array; public function show(int $id): mixed; public function items(int $id): mixed; public function finalize(int $id): mixed; }
