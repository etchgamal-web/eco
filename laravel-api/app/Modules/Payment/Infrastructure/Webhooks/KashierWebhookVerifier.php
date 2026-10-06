<?php

namespace App\Modules\Payment\Infrastructure\Webhooks;

use App\Modules\Payment\Domain\Contracts\KashierWebhookVerifierInterface;
use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;

final class KashierWebhookVerifier implements KashierWebhookVerifierInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings) {}

    public function verify(array $payload, string $signature = ''): bool
    {
        $data = (array) ($payload['data'] ?? []);
        $keys = $data['signatureKeys'] ?? null;
        $key = (string) $this->settings->value('kashier', 'payment_api_key', config('services.kashier.payment_api_key'));
        $provided = trim($signature);

        if (! is_array($keys) || $keys === [] || $key === '' || $provided === '') {
            return false;
        }

        $keys = array_values(array_filter($keys, 'is_string'));
        sort($keys, SORT_STRING);
        $parts = [];
        foreach ($keys as $field) {
            if (! array_key_exists($field, $data) || ! is_scalar($data[$field])) {
                return false;
            }
            $value = is_bool($data[$field]) ? ($data[$field] ? 'true' : 'false') : (string) $data[$field];
            $parts[] = $field.'='.rawurlencode($value);
        }

        if ($parts === []) {
            return false;
        }

        $calculated = hash_hmac('sha256', implode('&', $parts), $key);

        return hash_equals(strtolower($calculated), strtolower($provided));
    }
}
