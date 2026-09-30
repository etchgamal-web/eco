<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Modules\SocialCommerce\Application\UseCases\GetSocialSummary;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    public function stats(DashboardRequest $request, GetSocialSummary $socialSummary): JsonResponse
    {
        $activeOrders = CustomerOrder::query()->whereNotIn('status', ['cancelled', 'refunded']);
        $sales = (float) (clone $activeOrders)->sum('total_amount');
        $orders = (int) (clone $activeOrders)->count();

        return response()->json(['data' => [
            'sales' => ['total' => $sales],
            'orders' => [
                'total' => $orders,
                'new' => (int) CustomerOrder::query()->whereIn('status', ['pending', 'reviewing'])->count(),
                'by_status' => CustomerOrder::query()->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status'),
            ],
            'average_order' => $orders > 0 ? round($sales / $orders, 2) : 0,
            'currency' => (string) config('app.currency', 'SAR'),
            'social' => $socialSummary->execute(),
        ]]);
    }
}
