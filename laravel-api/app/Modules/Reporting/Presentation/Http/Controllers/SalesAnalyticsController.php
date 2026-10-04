<?php

namespace App\Modules\Reporting\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\Application\UseCases\GetSalesAnalytics;
use App\Modules\Reporting\Presentation\Http\Requests\SalesAnalyticsRequest;
use Illuminate\Http\JsonResponse;

final class SalesAnalyticsController extends Controller
{
    public function __invoke(SalesAnalyticsRequest $request, GetSalesAnalytics $analytics): JsonResponse
    {
        return response()->json(['data' => $analytics->execute((string) $request->validated('from'), (string) $request->validated('to'), (bool) $request->validated('compare', false))]);
    }
}
