<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Customer\Domain\Contracts\AdminCustomerQueryInterface;

final class GetAdminCustomers
{
    public function __construct(private readonly AdminCustomerQueryInterface $query) {}

    public function index(string $search = '', int $perPage = 20): array
    {
        return $this->query->index($search, $perPage);
    }

    public function show(int $customerId): array
    {
        return $this->query->show($customerId);
    }
}
