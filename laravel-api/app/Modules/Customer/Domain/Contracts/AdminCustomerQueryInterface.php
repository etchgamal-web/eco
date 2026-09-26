<?php

namespace App\Modules\Customer\Domain\Contracts;

interface AdminCustomerQueryInterface
{
    /** @return array{data: array, meta: array} */
    public function index(string $search, int $perPage): array;

    /** @return array{customer: mixed, orders: mixed} */
    public function show(int $customerId): array;
}
