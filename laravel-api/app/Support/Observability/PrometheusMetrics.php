<?php

namespace App\Support\Observability;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PrometheusMetrics
{
    public function recordHttpRequest(Request $request, Response $response, float $durationMs): void
    {
        if (! config('observability.metrics_enabled') || $request->is('metrics')) {
            return;
        }

        try {
            $route = $this->route($request);
            $method = strtoupper($request->method());
            $status = (string) $response->getStatusCode();
            $connection = Redis::connection(config('observability.metrics_connection', 'default'));
            $connection->hincrby('metrics:http:requests_total', $this->field($method, $route, $status), 1);
            $connection->hincrby('metrics:http:requests_by_route', $route, 1);
            $connection->hincrbyfloat('metrics:http:duration_ms_sum', $route, $durationMs);
            $connection->hincrby('metrics:http:duration_ms_count', $route, 1);
        } catch (Throwable) {
            // Metrics must never make an application request fail.
        }
    }

    public function render(): string
    {
        if (! config('observability.metrics_enabled')) {
            return "# HELP ecommerce_metrics_enabled Whether application metrics are enabled\n# TYPE ecommerce_metrics_enabled gauge\necommerce_metrics_enabled 0\n";
        }

        try {
            $connection = Redis::connection(config('observability.metrics_connection', 'default'));
            $lines = [
                '# HELP ecommerce_http_requests_total Total HTTP requests by method, route, and status.',
                '# TYPE ecommerce_http_requests_total counter',
            ];

            foreach ($connection->hgetall('metrics:http:requests_total') as $field => $value) {
                [$method, $route, $status] = explode('|', $field, 3);
                $lines[] = sprintf(
                    'ecommerce_http_requests_total{method="%s",route="%s",status="%s"} %s',
                    $this->escape($method),
                    $this->escape($route),
                    $this->escape($status),
                    $value,
                );
            }

            $lines[] = '# HELP ecommerce_http_request_duration_ms_sum Sum of HTTP request durations in milliseconds.';
            $lines[] = '# TYPE ecommerce_http_request_duration_ms_sum counter';
            foreach ($connection->hgetall('metrics:http:duration_ms_sum') as $route => $value) {
                $lines[] = sprintf('ecommerce_http_request_duration_ms_sum{route="%s"} %s', $this->escape($route), $value);
            }

            $lines[] = '# HELP ecommerce_http_request_duration_ms_count Number of HTTP request duration observations.';
            $lines[] = '# TYPE ecommerce_http_request_duration_ms_count counter';
            foreach ($connection->hgetall('metrics:http:duration_ms_count') as $route => $value) {
                $lines[] = sprintf('ecommerce_http_request_duration_ms_count{route="%s"} %s', $this->escape($route), $value);
            }

            $lines[] = '# HELP ecommerce_queue_failed_jobs Number of failed queue jobs.';
            $lines[] = '# TYPE ecommerce_queue_failed_jobs gauge';
            $lines[] = 'ecommerce_queue_failed_jobs '.($this->tableCount('failed_jobs') ?? 0);
            $lines[] = '# HELP ecommerce_outbox_pending_events Number of outbox events waiting to be dispatched.';
            $lines[] = '# TYPE ecommerce_outbox_pending_events gauge';
            $lines[] = 'ecommerce_outbox_pending_events '.($this->pendingOutboxCount() ?? 0);

            return implode("\n", $lines)."\n";
        } catch (Throwable) {
            return "# HELP ecommerce_metrics_available Whether metrics storage is available\n# TYPE ecommerce_metrics_available gauge\necommerce_metrics_available 0\n";
        }
    }

    private function route(Request $request): string
    {
        $route = $request->route();
        $uri = is_object($route) && method_exists($route, 'uri') ? $route->uri() : $request->path();

        return '/'.trim((string) $uri, '/');
    }

    private function field(string $method, string $route, string $status): string
    {
        return implode('|', [$method, $route, $status]);
    }

    private function escape(string $value): string
    {
        return addcslashes($value, "\\\"\n\r");
    }

    private function tableCount(string $table): ?int
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable) {
            return null;
        }
    }

    private function pendingOutboxCount(): ?int
    {
        try {
            return DB::table('outbox_events')->whereIn('status', ['pending', 'failed'])->count();
        } catch (Throwable) {
            return null;
        }
    }
}
