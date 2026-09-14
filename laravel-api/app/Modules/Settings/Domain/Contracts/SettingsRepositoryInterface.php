<?php

namespace App\Modules\Settings\Domain\Contracts;

use App\Modules\Settings\Domain\ValueObjects\SettingData;

interface SettingsRepositoryInterface
{
    public function findByKey(string $key): ?object;
    public function getByGroup(string $group): iterable;
    public function getAll(): iterable;
    public function save(SettingData $data): object;
    public function delete(string $key): bool;
}
