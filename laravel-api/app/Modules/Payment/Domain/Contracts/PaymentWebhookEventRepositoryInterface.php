<?php

namespace App\Modules\Payment\Domain\Contracts;

interface PaymentWebhookEventRepositoryInterface
{
    public function recordOrGet(array $attributes): object;

    public function markProcessed(string $provider, string $eventId): void;
}
