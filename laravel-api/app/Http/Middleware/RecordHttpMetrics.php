<?php

namespace App\Http\Middleware;

use App\Support\Observability\PrometheusMetrics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RecordHttpMetrics
{
    public function __construct(private readonly PrometheusMetrics $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        $this->metrics->recordHttpRequest($request, $response, $durationMs);

        return $response;
    }
}
