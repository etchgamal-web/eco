<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Domain\ValueObjects\StorefrontTheme;

final class GetStorefrontTheme
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    /** Returns the theme id the storefront should render. */
    public function execute(): string
    {
        $setting = $this->settings->findByKey(StorefrontTheme::SETTING_KEY);

        return StorefrontTheme::resolve($setting?->getTypedValue());
    }
}
