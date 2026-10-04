<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;

final class ManageAdminCustomer
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly AddressRepositoryInterface $addresses,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function update(int $customerId, array $data, ?object $actor): object
    {
        $customer = $this->customer($customerId);
        $before = $customer->only(['name', 'email', 'phone', 'status']);
        $updated = $this->customers->updateAdmin($customer, $data);
        $this->audit->record($actor, 'customer.updated', 'customer', $customerId, ['before' => $before, 'after' => $updated->only(['name', 'email', 'phone', 'status'])]);
        return $updated;
    }

    public function createAddress(int $customerId, array $data, ?object $actor): object
    {
        $this->customer($customerId);
        $address = $this->addresses->createForUser($customerId, $data);
        $this->audit->record($actor, 'customer.address.created', 'customer_address', $address->id, ['customer_id' => $customerId]);
        return $address;
    }

    public function updateAddress(int $customerId, int $addressId, array $data, ?object $actor): object
    {
        $this->customer($customerId);
        $address = $this->addresses->findForUser($customerId, $addressId);
        $before = $address->toArray();
        $updated = $this->addresses->update($address, $data);
        $this->audit->record($actor, 'customer.address.updated', 'customer_address', $addressId, ['customer_id' => $customerId, 'before' => $before, 'after' => $updated->toArray()]);
        return $updated;
    }

    public function deleteAddress(int $customerId, int $addressId, ?object $actor): void
    {
        $this->customer($customerId);
        $address = $this->addresses->findForUser($customerId, $addressId);
        $this->addresses->delete($address);
        $this->audit->record($actor, 'customer.address.deleted', 'customer_address', $addressId, ['customer_id' => $customerId]);
    }

    private function customer(int $id): object
    {
        $customer = $this->customers->findCustomerById($id);
        if ($customer === null) throw new CustomerNotFoundException($id);
        return $customer;
    }
}
