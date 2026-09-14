<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
use App\Modules\Customer\Domain\Exceptions\CustomerFeatureNotFoundException;

final class ManageCustomerAddress
{
    public function __construct(private readonly AuthenticationServiceInterface $auth, private readonly AddressRepositoryInterface $addresses) {}

    private function user(): object
    {
        $user = $this->auth->user();
        if (!$user) throw new AuthenticationException('Unauthenticated.');
        return $user;
    }

    public function create(array $data): object
    {
        return $this->addresses->createForUser($this->user()->id, $data);
    }

    public function update(int $id, array $data): object
    {
        $address = $this->find($id);
        return $this->addresses->update($address, $data);
    }

    public function delete(int $id): void
    {
        $this->addresses->delete($this->find($id));
    }

    private function find(int $id): object
    {
        try {
            return $this->addresses->findForUser($this->user()->id, $id);
        } catch (\Throwable $exception) {
            if ($exception instanceof \App\Modules\Customer\Domain\Exceptions\AddressNotFoundException) {
                throw new CustomerFeatureNotFoundException('Address', $id);
            }
            throw $exception;
        }
    }
}
