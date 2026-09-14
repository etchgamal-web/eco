<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingWebhookEventRepositoryInterface
{
    public function recordOrGet(array $attributes): object;

    public function markProcessed(object $event): object;

    public function markFailed(object $event, string $error): object;
}
