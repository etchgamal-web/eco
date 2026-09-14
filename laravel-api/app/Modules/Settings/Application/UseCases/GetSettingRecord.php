<?php
namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Domain\Exceptions\SettingsNotFoundException;

final class GetSettingRecord
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function execute(string $key): object
    {
        $setting = $this->settings->findByKey($key);

        if ($setting === null) {
            throw new SettingsNotFoundException($key);
        }

        return $setting;
    }
}
