<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Observability\PrometheusMetrics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MetricsController extends Controller
{
    public function __invoke(Request $request, PrometheusMetrics $metrics): Response
    {
        $configuredToken = (string) config('observability.metrics_token');
        $providedToken = (string) $request->bearerToken();

        abort_if($configuredToken === '', 404);
        abort_unless($providedToken !== '' && hash_equals($configuredToken, $providedToken), 401);

        return response($metrics->render())
            ->header('Content-Type', 'text/plain; version=0.0.4')
            ->header('Cache-Control', 'no-store');
    }
}
