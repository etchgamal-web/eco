<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class CheckoutSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::query()->firstOrNew(['key' => 'checkout.require_authentication']);
        if ($setting->exists) {
            return;
        }
        $setting->group = 'checkout';
        $setting->type = 'boolean';
        $setting->description = 'Require customers to sign in before checkout.';
        $setting->is_secret = false;
        $setting->is_encrypted = false;
        $setting->setTypedValue(true);
        $setting->save();
    }
}
