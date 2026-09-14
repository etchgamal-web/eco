<?php

namespace App\Modules\Settings\Domain\ValueObjects;

final readonly class SettingsData
{
    /** @param array<int, SettingData> $settings */
    public function __construct(public array $settings) {}

    public static function fromModels(iterable $settings): self
    {
        $items = [];
        foreach ($settings as $setting) {
            $items[] = SettingData::fromModel($setting);
        }
        return new self($items);
    }

    public function toArray(): array
    {
        return array_map(static fn (SettingData $setting) => $setting->toArray(), $this->settings);
    }
}
