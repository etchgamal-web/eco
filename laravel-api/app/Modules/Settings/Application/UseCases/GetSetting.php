<?php
namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;

final class GetSetting
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function execute(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings->findByKey($key);

        return $setting?->getTypedValue() ?? $default;
    }
}
