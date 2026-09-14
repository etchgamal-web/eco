<?php

namespace App\Modules\Shipping\Infrastructure\Webhooks;

use App\Modules\Shipping\Domain\Contracts\ShippingWebhookAuthenticatorInterface;
use App\Modules\Shipping\Infrastructure\Configuration\ShippingProviderSettings;

final class ShippingWebhookAuthenticator implements ShippingWebhookAuthenticatorInterface
{
    public function __construct(private readonly ShippingProviderSettings $settings)
    {
    }

    public function authenticate(string $header, string $value): bool
    {
        $expectedHeader = (string) $this->settings->value('bosta', 'webhook_auth_header', config('services.bosta.webhook_auth_header', 'Authorization'));
        $expectedValue = (string) $this->settings->value('bosta', 'webhook_auth_value', config('services.bosta.webhook_auth_value'));
        return $expectedHeader === $header && $expectedValue !== '' && hash_equals($expectedValue, $value);
    }
}
