<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\CheckoutPolicyInterface;
use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;

final class EloquentCheckoutPolicy implements CheckoutPolicyInterface
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {}

    public function allowsGuestCheckout(): bool
    {
        return (bool) ($this->settings->findByKey('checkout.require_authentication')?->getTypedValue() === false);
    }
}
