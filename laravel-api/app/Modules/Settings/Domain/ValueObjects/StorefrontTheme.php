<?php

namespace App\Modules\Settings\Domain\ValueObjects;

/**
 * Themes the storefront can render. The active one is chosen from the admin dashboard.
 * Keep the ids in sync with the storefront (src/core/theme/themes.ts) and admin (AppearancePage.tsx).
 */
final class StorefrontTheme
{
    public const SETTING_KEY = 'appearance.storefront_theme';

    public const THEMES = ['classic', 'dark-gold', 'bold'];

    public const DEFAULT = 'classic';

    /** Falls back to the default theme for anything that is not a known theme id. */
    public static function resolve(mixed $value): string
    {
        return is_string($value) && in_array($value, self::THEMES, true) ? $value : self::DEFAULT;
    }
}
