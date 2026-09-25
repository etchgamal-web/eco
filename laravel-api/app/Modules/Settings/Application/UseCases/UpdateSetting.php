<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Domain\ValueObjects\SettingData;

final class UpdateSetting
{
    /**
     * Create a new UpdateSetting use case instance.
     */
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {}

    /**
     * Execute the use case to create or update a setting.
     */
    public function execute(SettingData $data): object
    {
        return $this->settings->save($data);
    }
}
