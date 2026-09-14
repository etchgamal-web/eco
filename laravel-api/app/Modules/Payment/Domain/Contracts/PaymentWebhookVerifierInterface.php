<?php

namespace App\Modules\Payment\Domain\Contracts;

interface PaymentWebhookVerifierInterface
{
    public function verify(array $payload, string $signature = ''): bool;
}
