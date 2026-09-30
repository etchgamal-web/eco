<?php

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\UseCases\GetOperationalDashboard;
use App\Modules\Payment\Application\UseCases\ReconcilePayment;
use App\Shared\Application\UseCases\RetryFailedOutbox;
use App\Modules\Payment\Presentation\Http\Requests\PaymentManagementRequest;
use Illuminate\Http\JsonResponse;

final class OperationalDashboardController extends Controller
{
    public function __invoke(PaymentManagementRequest $request, GetOperationalDashboard $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->execute()]);
    }

    public function reconcile(PaymentManagementRequest $request, int $paymentId, ReconcilePayment $reconcile): JsonResponse
    {
        return response()->json(['data' => $reconcile->execute($paymentId, (int) $request->validated('operation_id'))]);
    }

    public function retryOutbox(PaymentManagementRequest $request, int $eventId, RetryFailedOutbox $retry): JsonResponse
    {
        try {
            $event = $retry->execute($eventId);
        } catch (\RuntimeException) {
            return response()->json(['message' => 'Only failed outbox events can be retried manually.'], 409);
        }

        return response()->json(['data' => $event]);
    }
}
