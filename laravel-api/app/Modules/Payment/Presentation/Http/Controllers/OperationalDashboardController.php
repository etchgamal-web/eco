<?php

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\UseCases\GetOperationalDashboard;
use App\Modules\Payment\Presentation\Http\Requests\PaymentManagementRequest;
use Illuminate\Http\JsonResponse;

final class OperationalDashboardController extends Controller
{
    public function __invoke(PaymentManagementRequest $request, GetOperationalDashboard $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->execute()]);
    }
}
