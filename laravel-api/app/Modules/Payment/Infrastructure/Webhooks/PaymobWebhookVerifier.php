<?php

namespace App\Modules\Payment\Infrastructure\Webhooks;

use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;
use App\Modules\Payment\Domain\Contracts\PaymobWebhookVerifierInterface;

final class PaymobWebhookVerifier implements PaymobWebhookVerifierInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings)
    {
    }

    public function verify(array $payload, string $providedHmac): bool
    {
        $secret = (string) $this->settings->value('paymob', 'hmac_secret', config('services.paymob.hmac_secret'));
        if ($secret === '' || $providedHmac === '') {
            return false;
        }

        $object = (array) ($payload['obj'] ?? $payload);
        $order = (array) ($object['order'] ?? []);
        $source = (array) ($object['source_data'] ?? []);
        $values = [
            'amount_cents' => $object['amount_cents'] ?? '',
            'created_at' => $object['created_at'] ?? '',
            'currency' => $object['currency'] ?? '',
            'error_occured' => $object['error_occured'] ?? '',
            'has_parent_transaction' => $object['has_parent_transaction'] ?? '',
            'id' => $object['id'] ?? '',
            'integration_id' => $object['integration_id'] ?? '',
            'is_3d_secure' => $object['is_3d_secure'] ?? '',
            'is_auth' => $object['is_auth'] ?? '',
            'is_capture' => $object['is_capture'] ?? '',
            'is_refunded' => $object['is_refunded'] ?? '',
            'is_standalone_payment' => $object['is_standalone_payment'] ?? '',
            'is_voided' => $object['is_voided'] ?? '',
            'order.id' => $order['id'] ?? '',
            'owner' => $object['owner'] ?? '',
            'pending' => $object['pending'] ?? '',
            'source_data.pan' => $source['pan'] ?? '',
            'source_data.sub_type' => $source['sub_type'] ?? '',
            'source_data.type' => $source['type'] ?? '',
            'success' => $object['success'] ?? '',
        ];
        ksort($values);
        $calculated = hash_hmac('sha512', implode('', array_map(static fn ($value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, $values)), $secret);

        return hash_equals(strtolower($calculated), strtolower($providedHmac));
    }
}
