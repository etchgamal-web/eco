<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiVersioningTest extends TestCase
{
    public function test_only_v1_routes_are_registered(): void
    {
        $apiRoutes = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() !== null && str_starts_with($route->uri(), 'api/')) {
                $apiRoutes[] = $route;
            }
        }

        $this->assertCount(119, $apiRoutes);
        $this->assertTrue(collect($apiRoutes)->every(
            static fn ($route): bool => str_starts_with($route->uri(), 'api/v1/')
        ));
        $this->assertNotNull(Route::getRoutes()->getByName('customer.checkout'));
        $this->assertNotNull(Route::getRoutes()->getByName('webhooks.paymob'));
        $this->assertNull(Route::getRoutes()->getByName('v1.customer.checkout'));
    }
}
