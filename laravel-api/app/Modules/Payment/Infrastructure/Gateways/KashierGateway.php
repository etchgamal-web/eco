<?php

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Infrastructure\Configuration\PaymentGatewaySettings;
use App\Shared\Domain\Exceptions\AmbiguousExternalResultException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class KashierGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings) {}

    public function supports(string $method): bool
    {
        return $method === 'kashier' && $this->settings->enabled('kashier', (bool) config('services.kashier.enabled', false));
    }

    public function createPayment(object $order, string $method, string $idempotencyKey): array
    {
        $merchantId = (string) $this->settings->value('kashier', 'merchant_id', config('services.kashier.merchant_id'));
        $secretKey = (string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key'));
        $paymentApiKey = (string) $this->settings->value('kashier', 'payment_api_key', config('services.kashier.payment_api_key'));
        $redirectUrl = (string) $this->settings->value('kashier', 'redirect_url', config('services.kashier.redirect_url'));
        $webhookUrl = (string) $this->settings->value('kashier', 'webhook_url', config('services.kashier.webhook_url'));
        if ($merchantId === '' || $secretKey === '' || $paymentApiKey === '' || $redirectUrl === '' || $webhookUrl === '') {
            throw new PaymentException('Kashier is not configured.');
        }

        $amount = number_format((float) $order->total_amount, 2, '.', '');
        $hashPath = '/?payment='.$merchantId.'.'.$idempotencyKey.'.'.$amount.'.'.$order->currency;
        $hash = hash_hmac('sha256', $hashPath, $paymentApiKey);
        $payload = [
            'expireAt' => now()->addHours(2)->format('Y-m-d H:i:sP'),
            'maxFailureAttempts' => 3,
            'paymentType' => 'credit',
            'amount' => $amount,
            'currency' => (string) $order->currency,
            'order' => $idempotencyKey,
            'merchantRedirect' => $redirectUrl,
            'display' => 'en',
            'type' => 'one-time',
            'allowedMethods' => 'card,wallet',
            'merchantId' => $merchantId,
            'failureRedirect' => false,
            'description' => 'Payment for order '.$order->id,
            'customer' => [
                'email' => (string) ($order->user?->email ?: 'customer@example.com'),
                'reference' => (string) $order->user_id,
            ],
            'interactionSource' => 'ECOMMERCE',
            'enable3DS' => true,
            'serverWebhook' => $webhookUrl,
        ];

        try {
            $response = $this->apiClient($secretKey, $paymentApiKey)->post('/v3/payment/sessions', $payload)->throw()->json();
        } catch (ConnectionException $exception) {
            throw new AmbiguousExternalResultException('Kashier payment creation result is unknown after a connection failure.', 0, $exception);
        }
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
        $sessionId = (string) ($payment->provider_reference ?: data_get($payment->metadata, 'session_id', ''));
        if ($sessionId === '') {
            throw new PaymentException('Kashier payment session reference is missing.');
        }
        $secretKey = (string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key'));
        $paymentApiKey = (string) $this->settings->value('kashier', 'payment_api_key', config('services.kashier.payment_api_key'));
        $response = $this->apiClient($secretKey, $paymentApiKey)
            ->get('/v3/payment/sessions/'.rawurlencode($sessionId).'/payment')->throw()->json();
        $details = (array) ($response['data'] ?? $response);
        if (! isset($details['amount'], $details['currency'])) {
            throw new PaymentException('Kashier reconciliation response omitted the amount or currency.');
        }
        if (abs((float) $details['amount'] - (float) $payment->amount) > 0.001 || strtoupper((string) $details['currency']) !== strtoupper((string) $payment->currency)) {
            throw new PaymentAmountMismatchException('Kashier reconciliation amount or currency does not match the local payment.');
        }
        $providerStatus = strtoupper((string) ($details['status'] ?? ''));
        $status = match ($providerStatus) {
            'SUCCESS', 'CAPTURED', 'PAID' => 'confirmed',
            'FAILURE', 'FAILED', 'DECLINED', 'EXPIRED', 'ABANDONED' => 'failed',
            'PENDING', 'CREATED', 'INITIATED', 'PROCESSING', 'OPENED' => 'pending',
            default => 'processing',
        };

        return ['status' => $status, 'provider_reference' => $sessionId, 'metadata' => ['provider' => 'kashier', 'reconciliation' => $details]];
    }

    public function refundPayment(object $payment): array
    {
        $orderId = (string) (data_get($payment->metadata, 'kashier_order_id') ?: $payment->provider_reference);
        if ($orderId === '') {
            throw new PaymentException('Kashier order reference is missing.');
        }
        $response = $this->fepClient((string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key')))
            ->put('/v3/orders/'.rawurlencode($orderId), [
                'apiOperation' => 'REFUND',
                'reason' => 'Customer refund',
                'transaction' => ['amount' => number_format((float) $payment->amount, 2, '.', '')],
            ])->throw()->json();
        if (($response['status'] ?? data_get($response, 'response.status')) !== 'SUCCESS') {
            throw new PaymentException('Kashier refund was not accepted.');
        }

        return ['status' => 'refunded', 'metadata' => ['provider' => 'kashier', 'refund_response' => $response]];
    }

    public function reconcileRefund(object $payment, object $operation): array
    {
        $endpoint = $this->settings->value('kashier', 'refund_status_url', config('services.kashier.refund_status_url'));
        if (! is_string($endpoint) || trim($endpoint) === '') {
            return ['status' => 'ambiguous', 'provider_reference' => $payment->provider_reference, 'metadata' => ['provider' => 'kashier', 'reason' => 'provider_refund_status_endpoint_not_configured']];
        }
        $reference = (string) ($operation->provider_reference ?: (data_get($payment->metadata, 'kashier_order_id') ?: $payment->provider_reference));
        $path = str_replace(['{reference}', '{operation_id}', '{return_id}'], [rawurlencode($reference), (string) $operation->id, (string) ($operation->return_id ?? '')], $endpoint);
        $response = $this->fepClient((string) $this->settings->value('kashier', 'secret_key', config('services.kashier.secret_key')))->get($path, ['refund_operation_id' => $operation->id, 'return_id' => $operation->return_id, 'requested_amount' => $operation->requested_amount])->throw()->json();
        $status = strtoupper((string) ($response['status'] ?? data_get($response, 'response.status', data_get($response, 'refund.status', ''))));

        return ['status' => in_array($status, ['REFUNDED', 'SUCCESS', 'SUCCEEDED', 'CONFIRMED'], true) ? 'refunded' : (in_array($status, ['FAILED', 'REJECTED', 'DECLINED'], true) ? 'failed' : 'ambiguous'), 'provider_reference' => $reference, 'confirmed_amount' => (int) ($response['confirmed_amount'] ?? data_get($response, 'refund.confirmed_amount', $response['refunded_amount'] ?? $operation->requested_amount)), 'metadata' => ['provider' => 'kashier', 'refund_operation_id' => $operation->id, 'return_id' => $operation->return_id, 'refund_reconciliation' => $response]];
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
