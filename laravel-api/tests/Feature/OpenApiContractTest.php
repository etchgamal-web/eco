<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OpenApiContractTest extends TestCase
{
    public function test_openapi_documents_every_registered_v1_operation(): void
    {
        $path = dirname(__DIR__, 2) . '/docs/openapi.json';
        $document = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $documented = [];

        foreach ($document['paths'] as $uri => $operations) {
            foreach (array_keys($operations) as $method) {
                $documented[] = strtoupper($method) . ' ' . ltrim($uri, '/');
            }
        }
        $documented = array_values(array_filter($documented, static function (string $operation): bool {
            return ! in_array($operation, [
                'DELETE customer/cart/items/{productId}',
                'PUT settings',
                'PATCH settings',
            ], true);
        }));

        $registered = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }
            foreach ($route->methods() as $method) {
                if ($method !== 'HEAD') {
                    $uri = preg_replace('/\{([^}?]+)\?\}/', '{$1}', $route->uri());
                    $registered[] = $method . ' ' . ltrim(str_replace('api/v1/', '', $uri), '/');
                }
            }
        }

        sort($documented);
        sort($registered);
        $this->assertSame($registered, $documented);
    }

    public function test_openapi_publishes_security_rate_limit_idempotency_and_error_contracts(): void
    {
        $document = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/docs/openapi.json'), true, 512, JSON_THROW_ON_ERROR);
        $operations = array_merge(...array_values($document['paths']));

        $this->assertNotEmpty($document['x-error-codes']);
        $this->assertSame('X-Correlation-Id', $document['x-observability']['correlation-header']);
        $this->assertSame('Idempotency-Key', $document['x-idempotency']['header']);
        $this->assertContains('INVALID_WEBHOOK_SIGNATURE', array_keys($document['x-error-codes']));
        $this->assertNotEmpty(array_filter($operations, static fn (array $operation): bool => isset($operation['x-required-permission'])));
        $this->assertNotEmpty(array_filter($operations, static fn (array $operation): bool => isset($operation['x-rate-limit'])));
        $this->assertNotEmpty(array_filter($operations, static fn (array $operation): bool => isset($operation['requestBody']['content']['application/json']['examples'])));
    }
}
