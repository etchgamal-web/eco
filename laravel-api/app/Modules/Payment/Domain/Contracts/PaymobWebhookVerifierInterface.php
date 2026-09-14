<?php

namespace App\Modules\Payment\Domain\Contracts;

interface PaymobWebhookVerifierInterface
{
    public function verify(array $payload, string $signature = ''): bool;
}
