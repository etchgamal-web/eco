<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CheckoutCustomerContextInterface
{
    public function cartForUser(int $userId): object;

    public function addressForUser(int $userId, int $addressId): object;

    public function clearCartForUser(int $userId): void;
}
