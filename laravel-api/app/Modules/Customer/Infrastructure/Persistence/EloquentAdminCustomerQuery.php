<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Customer\Domain\Contracts\AdminCustomerQueryInterface;
use App\Modules\Customer\Infrastructure\Models\CustomerAddress;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Staff\Infrastructure\Models\AuditLog;

final class EloquentAdminCustomerQuery implements AdminCustomerQueryInterface
{
    public function index(string $search, int $perPage): array
    {
        $customers = User::query()->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))
            ->select(['users.id', 'users.name', 'users.email', 'users.phone', 'users.status', 'users.created_at'])
            ->selectSub(CustomerOrder::query()->selectRaw('count(*)')->whereColumn('user_id', 'users.id'), 'orders_count')
            ->selectSub(CustomerOrder::query()->selectRaw('coalesce(sum(total_amount), 0)')->whereColumn('user_id', 'users.id'), 'total_spent')
            ->when($search !== '', fn ($query) => $query->where(fn ($filtered) => $filtered->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('users.created_at')->paginate($perPage);

        return ['data' => $customers->items(), 'meta' => ['current_page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'per_page' => $customers->perPage(), 'total' => $customers->total()]];
    }

    public function export(string $search): array
    {
        return User::query()->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))
            ->select(['users.id', 'users.name', 'users.email', 'users.phone', 'users.status', 'users.created_at'])
            ->selectSub(CustomerOrder::query()->selectRaw('count(*)')->whereColumn('user_id', 'users.id'), 'orders_count')
            ->selectSub(CustomerOrder::query()->selectRaw('coalesce(sum(total_amount), 0)')->whereColumn('user_id', 'users.id'), 'total_spent')
            ->when($search !== '', fn ($query) => $query->where(fn ($filtered) => $filtered->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('users.created_at')->get()->map(fn ($customer) => $customer->only(['id', 'name', 'email', 'phone', 'status', 'orders_count', 'total_spent', 'created_at']))->all();
    }

    public function show(int $customerId): array
    {
        $customer = User::query()->whereKey($customerId)->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))->firstOrFail();

        return ['customer' => $customer->only(['id', 'name', 'email', 'phone', 'status', 'created_at']), 'orders' => CustomerOrder::query()->where('user_id', $customer->id)->latest()->limit(10)->get(['id', 'order_number', 'status', 'total_amount', 'currency', 'created_at']), 'addresses' => CustomerAddress::query()->where('user_id', $customer->id)->latest()->get(), 'audit_logs' => AuditLog::query()->whereIn('target_type', [User::class, 'customer'])->where('target_id', $customer->id)->latest()->limit(20)->get()];
    }
}
