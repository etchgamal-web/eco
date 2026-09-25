<?php

namespace App\Modules\Order\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Application\UseCases\CancelOrder;
use App\Modules\Order\Application\UseCases\ConfirmOrder;
use App\Modules\Order\Application\UseCases\GetCustomerOrder;
use App\Modules\Order\Application\UseCases\GetOrder;
use App\Modules\Order\Application\UseCases\GetOrderTimeline;
use App\Modules\Order\Application\UseCases\ListCustomerOrders;
use App\Modules\Order\Application\UseCases\ListOrders;
use App\Modules\Order\Application\UseCases\RecordOrderContact;
use App\Modules\Order\Application\UseCases\SetOrderShippingCharge;
use App\Modules\Order\Application\UseCases\StartOrderReview;
use App\Modules\Order\Application\UseCases\UpdateOrderStatus;
use App\Modules\Order\Presentation\Http\Requests\OrderRequest;
use App\Modules\Order\Presentation\Http\Requests\OrderTimelineRequest;
use App\Modules\Order\Presentation\Http\Requests\OrderWorkflowRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function export(OrderRequest $request, ListOrders $orders): StreamedResponse
    {
        return response()->streamDownload(function () use ($orders): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['id', 'order_number', 'user_id', 'status', 'total_amount', 'currency', 'created_at']);
            foreach ($orders->execute() as $order) {
                fputcsv($output, [$order->id, $order->order_number, $order->user_id, $order->status, $order->total_amount, $order->currency, $order->created_at]);
            }
            fclose($output);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(OrderRequest $request, int $id, GetOrder $order): JsonResponse
    {
        return response()->json(['data' => $order->execute($id)]);
    }

    public function timeline(OrderTimelineRequest $request, int $id, GetOrderTimeline $timeline): JsonResponse
    {
        return response()->json(['data' => $timeline->execute($id)]);
    }

    public function updateStatus(OrderRequest $request, int $id, UpdateOrderStatus $update): JsonResponse
    {
        return response()->json(['data' => $update->execute($id, (string) $request->validated('status'))]);
    }

    public function setShippingCharge(OrderRequest $request, int $id, SetOrderShippingCharge $setCharge): JsonResponse
    {
        return response()->json(['data' => $setCharge->execute($id, (int) $request->validated('shipping_amount'))]);
    }

    public function cancel(OrderRequest $request, int $id, UpdateOrderStatus $update): JsonResponse
    {
        return response()->json(['data' => $update->execute($id, 'cancelled')]);
    }

    public function review(OrderWorkflowRequest $request, int $id, StartOrderReview $start): JsonResponse
    {
        return response()->json(['data' => $start->execute($id, (int) $request->user()->id)], 201);
    }

    public function contact(OrderWorkflowRequest $request, int $id, RecordOrderContact $record): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $record->execute($id, (string) $data['contact_result'], $data['notes'] ?? null)]);
    }

    public function confirm(OrderWorkflowRequest $request, int $id, ConfirmOrder $confirm): JsonResponse
    {
        return response()->json(['data' => $confirm->execute($id, (int) $request->user()->id)]);
    }
}
