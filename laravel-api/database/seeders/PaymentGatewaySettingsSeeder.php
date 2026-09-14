<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class PaymentGatewaySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['payment.default_method', 'kashier', 'string', false, 'Default hosted gateway for new checkout sessions.'],
            ['payment_gateways.paymob.enabled', false, 'boolean', false, 'Enable Paymob for customer payments.'],
            ['payment_gateways.paymob.base_url', 'https://accept.paymob.com', 'string', false, 'Paymob API base URL.'],
            ['payment_gateways.paymob.integration_ids', [], 'json', false, 'Paymob integration IDs.'],
            ['payment_gateways.paymob.secret_key', null, 'string', true, 'Paymob secret key.'],
            ['payment_gateways.paymob.public_key', null, 'string', true, 'Paymob public key.'],
            ['payment_gateways.paymob.hmac_secret', null, 'string', true, 'Paymob webhook HMAC secret.'],
            ['payment_gateways.paymob.notification_url', null, 'string', false, 'Paymob webhook URL.'],
            ['payment_gateways.paymob.redirection_url', null, 'string', false, 'Paymob browser return URL.'],
            ['payment_gateways.kashier.enabled', false, 'boolean', false, 'Enable Kashier for customer payments.'],
            ['payment_gateways.kashier.api_base_url', 'https://test-api.kashier.io', 'string', false, 'Kashier dashboard API base URL.'],
            ['payment_gateways.kashier.fep_base_url', 'https://test-fep.kashier.io', 'string', false, 'Kashier payment/FEP base URL.'],
            ['payment_gateways.kashier.merchant_id', null, 'string', true, 'Kashier merchant ID.'],
            ['payment_gateways.kashier.secret_key', null, 'string', true, 'Kashier secret key.'],
            ['payment_gateways.kashier.payment_api_key', null, 'string', true, 'Kashier Payment API Key.'],
            ['payment_gateways.kashier.webhook_url', null, 'string', false, 'Kashier webhook URL.'],
            ['payment_gateways.kashier.redirect_url', null, 'string', false, 'Kashier browser return URL.'],
            ['shipping_providers.bosta.enabled', false, 'boolean', false, 'Enable Bosta for shipping dispatch.'],
            ['shipping_providers.bosta.base_url', 'https://app.bosta.co', 'string', false, 'Bosta API base URL.'],
            ['shipping_providers.bosta.api_key', null, 'string', true, 'Bosta API key.'],
            ['shipping_providers.bosta.webhook_url', null, 'string', false, 'Bosta webhook URL.'],
            ['shipping_providers.bosta.webhook_auth_header', 'Authorization', 'string', false, 'Bosta webhook custom header name.'],
            ['shipping_providers.bosta.webhook_auth_value', null, 'string', true, 'Bosta webhook custom header value.'],
            ['shipping_providers.bosta.delivery_type', 10, 'integer', false, 'Bosta delivery order type.'],
            ['shipping_providers.bosta.package_type', 'Small', 'string', false, 'Bosta package type.'],
        ];

        foreach ($settings as [$key, $value, $type, $secret, $description]) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $wasExisting = $setting->exists;
            $wasEncrypted = (bool) ($setting->is_encrypted ?? false);
            $currentValue = $wasExisting ? $setting->getTypedValue() : $value;
            $setting->group = str_starts_with($key, 'payment_gateways.') ? 'payment_gateways' : (str_starts_with($key, 'shipping_providers.') ? 'shipping_providers' : 'payments');
            $setting->type = $type;
            $setting->description = $description;
            $setting->is_secret = $secret;
            $setting->is_encrypted = $secret;
            if (! $wasExisting || ($secret && ! $wasEncrypted)) {
                $setting->setTypedValue($currentValue);
            }
            $setting->save();
        }
    }
}
