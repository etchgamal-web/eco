<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CustomerPreferencesRepositoryInterface
{
    public function get(int $userId): object;
    public function update(int $userId, array $data): object;
}
