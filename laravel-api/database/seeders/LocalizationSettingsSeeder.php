<?php

namespace Database\Seeders;

use App\Modules\Settings\Infrastructure\Models\Setting;
use Illuminate\Database\Seeder;

final class LocalizationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'store.currency', 'value' => 'SAR', 'type' => 'string', 'description' => 'Default currency used by the store and dashboard.'],
            ['key' => 'store.locale', 'value' => 'ar', 'type' => 'string', 'description' => 'Default language and locale used by the store and dashboard.'],
        ];

        foreach ($settings as $data) {
            $setting = Setting::query()->firstOrNew(['key' => $data['key']]);
            $setting->group = 'localization';
            $setting->type = $data['type'];
            $setting->description = $data['description'];
            $setting->is_secret = false;
            $setting->is_encrypted = false;
            if (! $setting->exists) {
                $setting->setTypedValue($data['value']);
            }
            $setting->save();
        }
    }
}
