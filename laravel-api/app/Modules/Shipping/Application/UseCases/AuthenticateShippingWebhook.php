<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingWebhookAuthenticatorInterface;

final class AuthenticateShippingWebhook
{
    public function __construct(private readonly ShippingWebhookAuthenticatorInterface $authenticator)
    {
    }

    public function execute(string $header, string $value): bool
    {
        return $this->authenticator->authenticate($header, $value);
    }
}
