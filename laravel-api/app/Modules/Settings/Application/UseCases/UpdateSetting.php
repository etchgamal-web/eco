<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\ValueObjects\SettingData;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;

final class UpdateSetting
{
    /**
     * Create a new UpdateSetting use case instance.
     *
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Execute the use case to create or update a setting.
     *
     * @param SettingData $data
     * @return object
     */
    public function execute(SettingData $data): object
    {
        return $this->settings->save($data);
    }
}

