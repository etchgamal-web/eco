<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingWebhookAuthenticatorInterface
{
    public function authenticate(string $header, string $value): bool;
}
