<?php

namespace App\Modules\Payment\Infrastructure\Webhooks;

use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;
use App\Modules\Payment\Domain\Contracts\KashierWebhookVerifierInterface;

final class KashierWebhookVerifier implements KashierWebhookVerifierInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings)
    {
    }

    public function verify(array $payload, string $signature = ''): bool
    {
        $provided = (string) ($payload['signature'] ?? '');
        $key = (string) $this->settings->value('kashier', 'payment_api_key', config('services.kashier.payment_api_key'));
        if ($provided === '' || $key === '') {
            return false;
        }

        $fields = ['paymentStatus', 'cardDataToken', 'maskedCard', 'merchantOrderId', 'orderId', 'cardBrand', 'orderReference', 'transactionId', 'amount', 'currency'];
        $body = implode('&', array_map(static fn (string $field): string => $field . '=' . ($payload[$field] ?? 'null'), $fields));
        $calculated = hash_hmac('sha256', $body, $key);

        return hash_equals(strtolower($calculated), strtolower($provided));
    }
}
