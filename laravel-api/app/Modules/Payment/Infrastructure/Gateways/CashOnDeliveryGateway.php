<?php

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

final class CashOnDeliveryGateway implements PaymentGatewayInterface
{
    public function supports(string $method): bool
    {
        return $method === 'cash_on_delivery';
    }

    public function createPayment(object $order, string $method, string $idempotencyKey): array
    {
        return [
            'status' => 'pending',
            'provider_reference' => 'cod-'.Str::uuid()->toString(),
            'metadata' => ['method' => 'cash_on_delivery', 'idempotency_key' => $idempotencyKey],
        ];
    }

    public function confirmPayment(object $payment): array
    {
        return ['status' => 'paid', 'metadata' => ['confirmed_by' => 'cash_on_delivery']];
    }

    public function reconcilePayment(object $payment): array
    {
        return ['status' => $payment->status === 'confirmed' ? 'confirmed' : 'pending', 'provider_reference' => $payment->provider_reference];
    }

    public function refundPayment(object $payment): array
    {
        return ['status' => 'refunded', 'metadata' => ['refunded_by' => 'cash_on_delivery']];
    }
}
