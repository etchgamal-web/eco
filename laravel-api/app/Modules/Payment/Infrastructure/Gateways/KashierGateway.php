<?php

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class KashierGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings)
    {
    }

    public function supports(string $method): bool
    {
        return $method === 'kashier' && $this->settings->enabled('kashier', (bool) config('services.kashier.enabled', false));
    }

    public function createPayment(object $order, string $method, string $idempotencyKey): array
    {
        $merchantId = (string) $this->settings->value('kashier', 'merchant_id', config('services.kashier.merchant_id'));
        $secretKey = (string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key'));
        $paymentApiKey = (string) $this->settings->value('kashier', 'payment_api_key', config('services.kashier.payment_api_key'));
        if ($merchantId === '' || $secretKey === '' || $paymentApiKey === '') {
            throw new PaymentException('Kashier is not configured.');
        }

        $amount = number_format((float) $order->total_amount, 2, '.', '');
        $hashPath = '/?payment=' . $merchantId . '.' . $idempotencyKey . '.' . $amount . '.' . $order->currency;
        $hash = hash_hmac('sha256', $hashPath, $paymentApiKey);
        $payload = [
            'expireAt' => now()->addHours(2)->format('Y-m-d H:i:sP'),
            'maxFailureAttempts' => 3,
            'paymentType' => 'credit',
            'amount' => $amount,
            'currency' => (string) $order->currency,
            'order' => $idempotencyKey,
            'merchantRedirect' => $this->settings->value('kashier', 'redirect_url', config('services.kashier.redirect_url')),
            'display' => 'en',
            'type' => 'one-time',
            'allowedMethods' => 'card,wallet',
            'merchantId' => $merchantId,
            'failureRedirect' => false,
            'description' => 'Payment for order ' . $order->id,
            'customer' => [
                'email' => (string) ($order->user?->email ?: 'customer@example.com'),
                'reference' => (string) $order->user_id,
            ],
            'interactionSource' => 'ECOMMERCE',
            'enable3DS' => true,
            'serverWebhook' => $this->settings->value('kashier', 'webhook_url', config('services.kashier.webhook_url')),
        ];

        $response = $this->apiClient($secretKey, $paymentApiKey)->post('/v3/payment/sessions', $payload)->throw()->json();
        $sessionUrl = (string) ($response['sessionUrl'] ?? '');
        $sessionId = (string) ($response['_id'] ?? '');
        if ($sessionUrl === '' || $sessionId === '') {
            throw new PaymentException('Kashier returned an incomplete payment session.');
        }

        return [
            'status' => 'pending',
            'provider_reference' => $sessionId,
            'metadata' => [
                'provider' => 'kashier',
                'session_url' => $sessionUrl,
                'merchant_order_id' => $idempotencyKey,
                'hash' => $hash,
            ],
        ];
    }

    public function confirmPayment(object $payment): array
    {
        throw new PaymentException('Kashier payments are confirmed by webhook reconciliation.');
    }

    public function reconcilePayment(object $payment): array
    {
        $orderId = (string) ($payment->provider_reference ?: data_get($payment->metadata, 'kashier_order_id', ''));
        if ($orderId === '') {
            throw new PaymentException('Kashier reconciliation reference is missing.');
        }
        $response = $this->fepClient((string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key')))
            ->get('/v3/orders/' . rawurlencode($orderId))->throw()->json();
        $providerStatus = strtoupper((string) ($response['status'] ?? data_get($response, 'response.status', '')));
        $status = $providerStatus === 'SUCCESS' ? 'confirmed' : ($providerStatus === 'PENDING' ? 'pending' : 'failed');
        return ['status' => $status, 'provider_reference' => $orderId, 'metadata' => ['provider' => 'kashier', 'reconciliation' => $response]];
    }

    public function refundPayment(object $payment): array
    {
        $orderId = (string) ($payment->provider_reference ?: data_get($payment->metadata, 'kashier_order_id', ''));
        if ($orderId === '') {
            throw new PaymentException('Kashier order reference is missing.');
        }
        $response = $this->fepClient((string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key')))
            ->put('/v3/orders/' . rawurlencode($orderId), [
                'apiOperation' => 'REFUND',
                'reason' => 'Customer refund',
                'transaction' => ['amount' => number_format((float) $payment->amount, 2, '.', '')],
            ])->throw()->json();
        if (($response['status'] ?? data_get($response, 'response.status')) !== 'SUCCESS') {
            throw new PaymentException('Kashier refund was not accepted.');
        }
        return ['status' => 'refunded', 'metadata' => ['provider' => 'kashier', 'refund_response' => $response]];
    }

    private function apiClient(string $secretKey, string $paymentApiKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->settings->value('kashier', 'api_base_url', config('services.kashier.api_base_url')), '/'))
            ->acceptJson()->asJson()->withHeaders(['Authorization' => $secretKey, 'api-key' => $paymentApiKey])
            ->timeout((int) $this->settings->value('kashier', 'timeout', config('services.kashier.timeout', 15)))->retry(2, 250, throw: false);
    }

    private function fepClient(string $secretKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->settings->value('kashier', 'fep_base_url', config('services.kashier.fep_base_url')), '/'))
            ->acceptJson()->asJson()->withHeaders(['Authorization' => $secretKey])
            ->timeout((int) $this->settings->value('kashier', 'timeout', config('services.kashier.timeout', 15)))->retry(2, 250, throw: false);
    }
}
