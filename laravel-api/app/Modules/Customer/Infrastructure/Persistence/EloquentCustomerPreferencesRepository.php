<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Customer\Domain\Contracts\CustomerPreferencesRepositoryInterface;
use App\Modules\Customer\Infrastructure\Models\CustomerPreference;

final class EloquentCustomerPreferencesRepository implements CustomerPreferencesRepositoryInterface
{
    public function get(int $userId): object
    {
        return CustomerPreference::query()->firstOrCreate(['user_id' => $userId], ['data' => []]);
    }

    public function update(int $userId, array $data): object
    {
        $preferences = $this->get($userId);
        $preferences->update(['data' => $data]);

        return $preferences->fresh();
    }
}
