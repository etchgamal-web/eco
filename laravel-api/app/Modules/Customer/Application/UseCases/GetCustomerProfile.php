<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use App\Modules\Customer\Domain\Contracts\CustomerRepositoryInterface;

final class GetCustomerProfile
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly CustomerRepositoryInterface $customers,
    ) {
    }

    public function execute(): object
    {
        $user = $this->authentication->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $customer = $this->customers->findById((int) $user->getKey());

        if ($customer === null) {
            throw new CustomerNotFoundException((int) $user->getKey());
        }

        return $customer;
    }
}
