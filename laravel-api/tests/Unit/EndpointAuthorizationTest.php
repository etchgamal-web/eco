<?php

namespace Tests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

final class EndpointAuthorizationTest extends TestCase
{
    public function test_every_api_endpoint_is_v1_and_has_an_explicit_security_boundary(): void
    {
        $routes = collect(RouteFacade::getRoutes())->filter(
            static fn (Route $route): bool => str_starts_with($route->uri(), 'api/')
        );

        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            $this->assertTrue(
                str_starts_with($route->uri(), 'api/v1/'),
                'Legacy API route registered: ' . $route->uri()
            );

            $public = $this->isPublicRoute($route);
            if (! $public) {
                $this->assertContains('auth', $route->gatherMiddleware(), 'Missing auth middleware: ' . $route->uri());
            }

            [$controller, $method] = $this->controllerAction($route);
            $requestClass = $this->formRequestClass($controller, $method);
            $this->assertNotNull($requestClass, 'Missing FormRequest: ' . $route->uri());

            $source = file_get_contents((new \ReflectionClass($requestClass))->getFileName());
            $this->assertIsString($source);
            if (! $public) {
                $this->assertTrue(
                    str_contains($source, 'authorizePermission(')
                    || str_contains($source, 'authenticatedUser()')
                    || in_array($requestClass, [
                        \App\Modules\Auth\Presentation\Http\Requests\AuthRequest::class,
                        \App\Modules\Auth\Presentation\Http\Requests\ChangePasswordRequest::class,
                    ], true),
                    'Missing permission check: ' . $requestClass . ' for ' . $route->uri()
                );
            }
        }
    }

    private function isPublicRoute(Route $route): bool
    {
        $name = (string) $route->getName();
        if (in_array($name, ['auth.register', 'auth.login', 'webhooks.paymob', 'webhooks.kashier', 'webhooks.bosta', 'customer.checkout'], true)) {
            return true;
        }

        return in_array('GET', $route->methods(), true)
            && preg_match('#^api/v1/products(?:/[^/]+|/[^/]+/variants(?:/[^/]+)?)?$#', $route->uri()) === 1;
    }

    /** @return array{0: class-string, 1: string} */
    private function controllerAction(Route $route): array
    {
        $action = $route->getAction('controller');
        $this->assertIsString($action);
        [$controller, $method] = str_contains($action, '@') ? explode('@', $action, 2) : [$action, '__invoke'];

        return [$controller, $method];
    }

    /** @param class-string $controller */
    private function formRequestClass(string $controller, string $method): ?string
    {
        $reflection = new ReflectionMethod($controller, $method);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            $class = $type->getName();
            if (is_a($class, FormRequest::class, true)) {
                return $class;
            }
        }

        return null;
    }
}
