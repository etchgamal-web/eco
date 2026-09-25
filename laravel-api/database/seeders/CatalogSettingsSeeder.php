<?php

namespace Database\Seeders;

use App\Modules\Settings\Infrastructure\Models\Setting;
use Illuminate\Database\Seeder;

final class CatalogSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::query()->firstOrNew(['key' => 'catalog.sku_prefix']);
        $setting->group = 'catalog';
        $setting->type = 'string';
        $setting->description = 'Prefix used for automatically generated product variant SKUs.';
        $setting->is_secret = false;
        $setting->is_encrypted = false;
        if (! $setting->exists) {
            $setting->setTypedValue('SKU');
        }
        $setting->save();
    }
}
