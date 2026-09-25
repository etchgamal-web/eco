<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Customer\Domain\Contracts\CustomerOrderRepositoryInterface;

final class EloquentCustomerOrderRepository implements CustomerOrderRepositoryInterface
{
    public function listForUser(int $userId): iterable
    {
        return CustomerOrder::query()
            ->with('items.product')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
