<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class MonitoringMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_requires_a_bearer_token(): void
    {
        Config::set('observability.metrics_enabled', true);
        Config::set('observability.metrics_token', 'test-metrics-token');

        $this->get('/metrics')->assertUnauthorized();
        $this->withToken('wrong-token')->get('/metrics')->assertUnauthorized();
    }

    public function test_metrics_endpoint_returns_prometheus_content(): void
    {
        Config::set('observability.metrics_enabled', true);
        Config::set('observability.metrics_token', 'test-metrics-token');

        $response = $this->withToken('test-metrics-token')->get('/metrics');

        $response->assertOk()->assertSee('ecommerce_', false);
        self::assertStringStartsWith('text/plain; version=0.0.4', (string) $response->headers->get('Content-Type'));
    }
}
