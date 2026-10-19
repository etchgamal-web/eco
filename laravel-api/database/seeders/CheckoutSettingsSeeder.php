<?php

namespace Database\Seeders;

use App\Modules\Settings\Infrastructure\Models\Setting;
use Illuminate\Database\Seeder;

final class CheckoutSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = Setting::query()->firstOrNew(['key' => 'store.order_number_prefix']);
        if (! $prefix->exists) {
            $prefix->group = 'localization';
            $prefix->type = 'string';
            $prefix->description = 'Prefix used for customer-facing order numbers.';
            $prefix->is_secret = false;
            $prefix->is_encrypted = false;
            $prefix->setTypedValue('ECO');
            $prefix->save();
        }

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
