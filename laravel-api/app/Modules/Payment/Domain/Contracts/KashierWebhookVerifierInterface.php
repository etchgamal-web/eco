<?php

namespace App\Modules\Payment\Domain\Contracts;

interface KashierWebhookVerifierInterface
{
    public function verify(array $payload, string $signature = ''): bool;
}
