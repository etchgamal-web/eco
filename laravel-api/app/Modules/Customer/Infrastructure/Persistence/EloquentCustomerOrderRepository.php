<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Customer\Domain\Contracts\CustomerOrderRepositoryInterface;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;

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
