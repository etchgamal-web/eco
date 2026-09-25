<?php

namespace App\Modules\Order\Domain\Contracts;

interface CheckoutPolicyInterface
{
    public function allowsGuestCheckout(): bool;
}
