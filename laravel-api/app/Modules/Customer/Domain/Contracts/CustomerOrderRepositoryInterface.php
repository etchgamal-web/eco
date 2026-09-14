<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CustomerOrderRepositoryInterface
{
    public function listForUser(int $userId): iterable;
}
