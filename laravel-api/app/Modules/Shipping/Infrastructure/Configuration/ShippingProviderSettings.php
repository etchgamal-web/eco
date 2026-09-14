<?php

namespace App\Modules\Shipping\Infrastructure\Configuration;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;

final class ShippingProviderSettings
{
    public function __construct(private readonly SettingsRepositoryInterface $settings)
    {
    }

    public function value(string $provider, string $key, mixed $fallback = null): mixed
    {
        $setting = $this->settings->findByKey('shipping_providers.' . $provider . '.' . $key);
        if ($setting === null) {
            return $fallback;
        }
        $value = $setting->getTypedValue();
        return $value === null || $value === '' ? $fallback : $value;
    }

    public function enabled(string $provider, bool $fallback = false): bool
    {
        return (bool) $this->value($provider, 'enabled', $fallback);
    }
}
