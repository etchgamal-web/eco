<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
use App\Modules\Customer\Domain\Exceptions\CustomerFeatureNotFoundException;

final class GetDefaultCustomerAddress
{
    public function __construct(private readonly AuthenticationServiceInterface $auth, private readonly AddressRepositoryInterface $addresses) {}

    public function execute(): object
    {
        $user = $this->auth->user();
        if (!$user) throw new AuthenticationException('Unauthenticated.');
        $address = $this->addresses->defaultForUser($user->id);
        if (!$address) throw new CustomerFeatureNotFoundException('Default address', 0);
        return $address;
    }
}
