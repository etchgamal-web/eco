<?php

namespace App\Modules\Payment\Infrastructure\Gateways;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Infrastructure\Resilience\ProviderCircuitBreaker;

final class PaymentGatewayRouter implements PaymentGatewayInterface
{
    /** @var list<PaymentGatewayInterface> */
    private array $gateways;

    public function __construct(CashOnDeliveryGateway $cashOnDelivery, PaymobGateway $paymob, KashierGateway $kashier, private readonly ProviderCircuitBreaker $circuits)
    {
        $this->gateways = [$cashOnDelivery, $paymob, $kashier];
    }

    public function supports(string $method): bool
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($method)) {
                return true;
            }
        }
        return false;
    }

    public function createPayment(object $order, string $method, string $idempotencyKey): array
    {
        return $this->call($method, fn () => $this->gatewayFor($method)->createPayment($order, $method, $idempotencyKey));
    }

    public function confirmPayment(object $payment): array
    {
        return $this->call((string) $payment->method, fn () => $this->gatewayFor((string) $payment->method)->confirmPayment($payment));
    }

    public function reconcilePayment(object $payment): array
    {
        return $this->call((string) $payment->method, fn () => $this->gatewayFor((string) $payment->method)->reconcilePayment($payment));
    }

    public function refundPayment(object $payment): array
    {
        return $this->call((string) $payment->method, fn () => $this->gatewayFor((string) $payment->method)->refundPayment($payment));
    }

    private function call(string $method, callable $operation): array
    {
        return $this->circuits->call(match ($method) {
            'paymob' => 'paymob', 'kashier' => 'kashier', default => 'cash_on_delivery',
        }, $operation);
    }

    private function gatewayFor(string $method): PaymentGatewayInterface
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($method)) {
                return $gateway;
            }
        }
        throw new PaymentException('Unsupported payment method.');
    }
}
