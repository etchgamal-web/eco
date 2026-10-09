<?php

namespace App\Modules\Settings\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Infrastructure\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Public, read-only endpoint the storefront uses to know which theme to render.
 * The value is chosen from the admin dashboard (Appearance page) and stored in the settings table.
 */
class PublicAppearanceController extends Controller
{
    public const THEME_KEY = 'appearance.storefront_theme';

    public const THEMES = ['classic', 'dark-gold', 'bold'];

    public const DEFAULT_THEME = 'classic';

    public function show(SettingsRepositoryInterface $settings): JsonResponse
    {
        $record = $settings->findByKey(self::THEME_KEY);
        $theme = $record instanceof Setting ? $record->getTypedValue() : null;

        return response()
            ->json(['data' => ['theme' => is_string($theme) && in_array($theme, self::THEMES, true) ? $theme : self::DEFAULT_THEME]])
            ->header('Cache-Control', 'public, max-age=30');
    }
}
