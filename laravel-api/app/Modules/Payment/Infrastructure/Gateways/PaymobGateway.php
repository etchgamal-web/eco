<?php

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class PaymobGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings)
    {
    }

    public function supports(string $method): bool
    {
        return $method === 'paymob' && $this->settings->enabled('paymob', (bool) config('services.paymob.enabled', false));
    }

    public function createPayment(object $order, string $method, string $idempotencyKey): array
    {
        $secretKey = (string) $this->settings->value('paymob', 'secret_key', config('services.paymob.secret_key'));
        $publicKey = (string) $this->settings->value('paymob', 'public_key', config('services.paymob.public_key'));
        $integrationIds = $this->settings->value('paymob', 'integration_ids', config('services.paymob.integration_ids', []));

        if ($secretKey === '' || $publicKey === '' || $integrationIds === []) {
            throw new PaymentException('Paymob is not configured.');
        }

        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => (string) $item->name,
                'amount' => (int) $item->total_amount,
                'description' => (string) ($item->sku ?: $item->name),
                'quantity' => (int) $item->quantity,
            ];
        }
        if ((int) $order->shipping_amount > 0) {
            $items[] = [
                'name' => 'Shipping',
                'amount' => (int) $order->shipping_amount,
                'description' => 'Shipping fee',
                'quantity' => 1,
            ];
        }

        $billing = $this->billingData($order);
        $payload = [
            'amount' => (int) $order->total_amount,
            'currency' => (string) $order->currency,
            'payment_methods' => array_values($integrationIds),
            'items' => $items,
            'billing_data' => $billing,
            'special_reference' => $idempotencyKey,
            'notification_url' => $this->settings->value('paymob', 'notification_url', config('services.paymob.notification_url')),
            'redirection_url' => $this->settings->value('paymob', 'redirection_url', config('services.paymob.redirection_url')),
        ];

        $response = $this->client($secretKey)->post('/v1/intention/', $payload)->throw()->json();
        $clientSecret = (string) ($response['client_secret'] ?? '');
        $intentionId = $response['id'] ?? $response['order_id'] ?? null;
        if ($clientSecret === '' || $intentionId === null) {
            throw new PaymentException('Paymob returned an incomplete payment intention.');
        }

        return [
            'status' => 'pending',
            'provider_reference' => (string) $intentionId,
            'metadata' => [
                'provider' => 'paymob',
                'client_secret' => $clientSecret,
                'checkout_url' => rtrim((string) config('services.paymob.base_url'), '/') . '/unifiedcheckout/?publicKey=' . urlencode($publicKey) . '&clientSecret=' . urlencode($clientSecret),
                'idempotency_key' => $idempotencyKey,
            ],
        ];
    }

    public function confirmPayment(object $payment): array
    {
        throw new PaymentException('Paymob payments are confirmed by callback/webhook reconciliation.');
    }

    public function reconcilePayment(object $payment): array
    {
        $reference = (string) data_get($payment->metadata, 'transaction_id', $payment->provider_reference);
        if ($reference === '') {
            throw new PaymentException('Paymob reconciliation reference is missing.');
        }
        $response = $this->client((string) $this->settings->value('paymob', 'secret_key', config('services.paymob.secret_key')))
            ->get('/api/acceptance/transactions/' . rawurlencode($reference))->throw()->json();
        $status = (bool) ($response['success'] ?? false) ? 'confirmed' : ((bool) ($response['pending'] ?? false) ? 'pending' : 'failed');
        return ['status' => $status, 'provider_reference' => $reference, 'metadata' => ['provider' => 'paymob', 'reconciliation' => $response]];
    }

    public function refundPayment(object $payment): array
    {
        $transactionId = data_get($payment->metadata, 'transaction_id', $payment->provider_reference);
        if (! $transactionId) {
            throw new PaymentException('Paymob transaction reference is missing.');
        }

        $response = $this->client((string) $this->settings->value('paymob', 'secret_key', config('services.paymob.secret_key')))
            ->post('/api/acceptance/void_refund/refund', [
                'transaction_id' => (int) $transactionId,
                'amount_cents' => (int) $payment->amount,
            ])->throw()->json();

        if (($response['success'] ?? true) !== true) {
            throw new PaymentException('Paymob refund was not accepted.');
        }

        return [
            'status' => 'refunded',
            'metadata' => ['provider' => 'paymob', 'refund_response' => $response],
        ];
    }

    private function client(string $secretKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->settings->value('paymob', 'base_url', config('services.paymob.base_url')), '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($secretKey, 'Token')
            ->timeout((int) $this->settings->value('paymob', 'timeout', config('services.paymob.timeout', 15)))
            ->retry(2, 250, throw: false);
    }

    private function billingData(object $order): array
    {
        $user = $order->user;
        $name = trim((string) ($user?->name ?: data_get($order->shipping_address, 'recipient_name', 'Customer')));
        $parts = preg_split('/\s+/', $name, 2) ?: ['Customer'];

        return [
            'apartment' => 'NA',
            'first_name' => $parts[0] ?: 'Customer',
            'last_name' => $parts[1] ?? 'Customer',
            'street' => (string) data_get($order->shipping_address, 'address_line1', 'NA'),
            'building' => 'NA',
            'phone_number' => (string) data_get($order->shipping_address, 'phone', $user?->phone),
            'city' => (string) data_get($order->shipping_address, 'city', 'NA'),
            'country' => (string) data_get($order->shipping_address, 'country', 'EG'),
            'email' => (string) ($user?->email ?: 'customer@example.com'),
            'floor' => 'NA',
            'state' => (string) data_get($order->shipping_address, 'state', 'NA'),
        ];
    }
}
