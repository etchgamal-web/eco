<?php

namespace App\Modules\Order\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Application\UseCases\CancelOrder;
use App\Modules\Order\Application\UseCases\GetCustomerOrder;
use App\Modules\Order\Application\UseCases\GetOrder;
use App\Modules\Order\Application\UseCases\ListCustomerOrders;
use App\Modules\Order\Application\UseCases\ListOrders;
use App\Modules\Order\Application\UseCases\UpdateOrderStatus;
use App\Modules\Order\Presentation\Http\Requests\OrderRequest;
use Illuminate\Http\JsonResponse;

final class OrderController extends Controller
{
    public function customerIndex(OrderRequest $request, ListCustomerOrders $orders): JsonResponse
    {
        return response()->json(['data' => $orders->execute()]);
    }

    public function customerShow(OrderRequest $request, int $id, GetCustomerOrder $order): JsonResponse
    {
        return response()->json(['data' => $order->execute($id)]);
    }

    public function customerCancel(OrderRequest $request, int $id, CancelOrder $cancel): JsonResponse
    {
        return response()->json(['data' => $cancel->execute($id)]);
    }

    public function index(OrderRequest $request, ListOrders $orders): JsonResponse
    {
        return response()->json(['data' => $orders->execute()]);
    }

    public function show(OrderRequest $request, int $id, GetOrder $order): JsonResponse
    {
        return response()->json(['data' => $order->execute($id)]);
    }

    public function updateStatus(OrderRequest $request, int $id, UpdateOrderStatus $update): JsonResponse
    {
        return response()->json(['data' => $update->execute($id, (string) $request->validated('status'))]);
    }

    public function cancel(OrderRequest $request, int $id, UpdateOrderStatus $update): JsonResponse
    {
        return response()->json(['data' => $update->execute($id, 'cancelled')]);
    }
}
