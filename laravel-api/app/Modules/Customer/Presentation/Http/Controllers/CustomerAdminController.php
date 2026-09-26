<?php

namespace App\Modules\Customer\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Customer\Presentation\Http\Requests\CustomerAdminRequest;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use Illuminate\Http\JsonResponse;

final class CustomerAdminController extends Controller
{
    public function index(CustomerAdminRequest $request): JsonResponse
    {
        $search = trim((string) $request->validated('search', ''));
        $perPage = (int) $request->validated('per_page', 20);

        $query = User::query()
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))
            ->select(['users.id', 'users.name', 'users.email', 'users.phone', 'users.status', 'users.created_at'])
            ->selectSub(CustomerOrder::query()->selectRaw('count(*)')->whereColumn('user_id', 'users.id'), 'orders_count')
            ->selectSub(CustomerOrder::query()->selectRaw('coalesce(sum(total_amount), 0)')->whereColumn('user_id', 'users.id'), 'total_spent')
            ->when($search !== '', fn ($customers) => $customers->where(fn ($filtered) => $filtered->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('users.created_at');

        $customers = $query->paginate($perPage);

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(CustomerAdminRequest $request, int $customerId): JsonResponse
    {
        $customer = User::query()
            ->whereKey($customerId)
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))
            ->firstOrFail();

        return response()->json(['data' => [
            'customer' => $customer->only(['id', 'name', 'email', 'phone', 'status', 'created_at']),
            'orders' => CustomerOrder::query()
                ->where('user_id', $customer->id)
                ->latest()
                ->limit(10)
                ->get(['id', 'order_number', 'status', 'total_amount', 'currency', 'created_at']),
        ]]);
    }
}
