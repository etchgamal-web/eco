<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Collection;

final class GetSettingsByGroup
{
    /**
     * Create a new GetSettingsByGroup use case instance.
     *
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Execute the use case to retrieve settings by group.
     *
     * @param string $group
     * @return Collection<int, Setting>
     */
    public function execute(string $group): Collection
    {
        return $this->settings->getByGroup($group);
    }
}
