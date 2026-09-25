<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * These tests are executable architecture rules.
 * A new endpoint must remain Controller -> Form Request -> Use Case -> Contract -> Infrastructure.
 */
final class StrictArchitectureTest extends TestCase
{
    public function test_domain_is_framework_and_outer_layer_independent(): void
    {
        foreach ($this->filesIn('Domain') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'Application\\',
                'Infrastructure\\',
                'Presentation\\',
                'App\\Models\\',
                'Illuminate\\Database\\',
                'Illuminate\\Http\\',
                'Illuminate\\Support\\Facades\\',
            ], $file);
        }
    }

    public function test_domain_does_not_depend_on_another_bounded_context(): void
    {
        foreach ($this->filesIn('Domain') as $file) {
            $source = $this->source($file);
            $relative = str_replace('\\', '/', $file);
            preg_match('#/Modules/([^/]+)/Domain/#', $relative, $ownerMatch);
            $owner = $ownerMatch[1] ?? '';
            preg_match_all('/App\\\\Modules\\\\([A-Za-z0-9_]+)\\\\Domain\\\\/', $source, $matches);
            foreach (array_unique($matches[1] ?? []) as $dependency) {
                self::assertTrue($dependency === $owner || $dependency === 'Shared', $file.' crosses bounded-context Domain boundary.');
            }
        }
    }

    public function test_application_cannot_bypass_domain_contracts_or_infrastructure(): void
    {
        foreach ($this->filesIn('Application') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'Infrastructure\\',
                'Presentation\\',
                'Illuminate\\Database\\',
                'Illuminate\\Support\\Facades\\',
                'App\\Models\\',
                '::query(',
                'auth()',
                'forceFill(',
                'DB::',
                'Model::',
            ], $file);
        }
    }

    public function test_infrastructure_cannot_depend_on_application_or_http(): void
    {
        foreach ($this->filesIn('Infrastructure') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'Application\\',
                'Presentation\\',
                'Http\\Controllers\\',
            ], $file);
        }
    }

    public function test_shared_application_does_not_depend_on_business_modules(): void
    {
        foreach ($this->filesIn('Shared/Application') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'App\\Modules\\Auth\\',
                'App\\Modules\\Catalog\\',
                'App\\Modules\\Customer\\',
                'App\\Modules\\Inventory\\',
                'App\\Modules\\Order\\',
                'App\\Modules\\Payment\\',
                'App\\Modules\\Promotion\\',
                'App\\Modules\\Shipping\\',
                'App\\Modules\\SocialCommerce\\',
                'App\\Modules\\Staff\\',
                'App\\Modules\\Tax\\',
            ], $file);
        }
    }

    public function test_order_checkout_uses_an_order_owned_payment_port(): void
    {
        $file = dirname(__DIR__, 2).'/app/Modules/Order/Application/UseCases/Checkout.php';
        $source = $this->source($file);

        self::assertStringContainsString('PaymentInitiatorInterface', $source);
        self::assertStringNotContainsString('App\\Modules\\Payment\\', $source);
        self::assertStringNotContainsString('CreatePayment', $source);
        self::assertStringNotContainsString('PaymentData', $source);
    }

    public function test_checkout_flow_is_not_owned_by_the_order_repository(): void
    {
        $repository = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Infrastructure/Persistence/EloquentOrderRepository.php');
        $service = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Application/Services/CheckoutOrderService.php');
        $gateway = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Infrastructure/Persistence/EloquentCheckoutGateway.php');

        self::assertStringNotContainsString('function checkout(', $repository);
        self::assertStringNotContainsString('function checkoutGuest(', $repository);
        self::assertStringContainsString('CheckoutGatewayInterface', $service);
        self::assertStringContainsString('TransactionManagerInterface', $service);
        self::assertStringContainsString('CheckoutGatewayInterface', $gateway);
    }

    public function test_auth_user_does_not_own_business_module_relationships(): void
    {
        $user = $this->source(dirname(__DIR__, 2).'/app/Modules/Auth/Infrastructure/Models/User.php');

        self::assertStringNotContainsString('App\\Modules\\Customer\\Infrastructure\\', $user);
        self::assertStringNotContainsString('App\\Modules\\Order\\Infrastructure\\', $user);
    }

    public function test_order_checkout_uses_customer_public_context_port(): void
    {
        $service = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Application/Services/CheckoutOrderService.php');
        $gateway = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Infrastructure/Persistence/EloquentCheckoutGateway.php');

        self::assertStringContainsString('CheckoutCustomerContextInterface', $service);
        self::assertStringNotContainsString('customerContext(', $gateway);
        self::assertStringNotContainsString('App\\Modules\\Auth\\Infrastructure\\Models\\User', $gateway);
    }

    public function test_cross_module_checkout_adapters_use_public_ports(): void
    {
        $cart = $this->source(dirname(__DIR__, 2).'/app/Modules/Customer/Infrastructure/Persistence/EloquentCartRepository.php');
        $gateway = $this->source(dirname(__DIR__, 2).'/app/Modules/Order/Infrastructure/Persistence/EloquentCheckoutGateway.php');

        self::assertStringContainsString('ProductReaderInterface', $cart);
        self::assertStringContainsString('InventoryRepositoryInterface', $cart);
        self::assertStringNotContainsString('App\\Modules\\Catalog\\Infrastructure\\Models\\', $cart);
        self::assertStringNotContainsString('App\\Modules\\Inventory\\Infrastructure\\Models\\', $cart);
        self::assertStringContainsString('ProductReaderInterface', $gateway);
        self::assertStringContainsString('CouponServiceInterface', $gateway);
        self::assertStringNotContainsString('App\\Modules\\Catalog\\Infrastructure\\Models\\', $gateway);
        self::assertStringNotContainsString('App\\Modules\\Promotion\\Infrastructure\\Models\\', $gateway);
    }

    public function test_order_models_are_owned_by_order_infrastructure_without_legacy_wrappers(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['CustomerOrder', 'CustomerOrderItem', 'OrderActivity', 'OrderReview'] as $model) {
            $infrastructure = $this->source($root.'/app/Modules/Order/Infrastructure/Models/'.$model.'.php');

            self::assertStringContainsString('namespace App\Modules\Order\Infrastructure\Models;', $infrastructure);
            self::assertFileDoesNotExist($root.'/app/Models/'.$model.'.php');
        }
    }

    public function test_legacy_app_models_directory_is_removed(): void
    {
        self::assertDirectoryDoesNotExist(dirname(__DIR__, 2).'/app/Models');
    }

    public function test_every_controller_is_thin_and_uses_form_request_and_use_case(): void
    {
        foreach ($this->filesIn('Presentation/Http/Controllers') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'Domain\\Contracts',
                'RepositoryInterface',
                'Infrastructure\\',
                'App\\Models\\',
                'Illuminate\\Database\\',
                'Illuminate\\Support\\Facades\\',
                'DB::',
                'Model::',
                '->save(',
                '->create(',
                '->delete(',
            ], $file);
            self::assertStringContainsString('Application\\UseCases\\', $source, $file);

            foreach ($this->publicActions($source) as $action) {
                self::assertMatchesRegularExpression('/(?:^|,)\s*\w+Request\s+\$\w+\b/', $action, $file);
                self::assertStringContainsString('Application\\UseCases\\', $source, $file);
            }
        }
    }

    public function test_every_http_request_is_a_form_request(): void
    {
        foreach ($this->filesIn('Presentation/Http/Requests') as $file) {
            $source = $this->source($file);
            self::assertMatchesRegularExpression('/extends\s+FormRequest\b/', $source, $file);
            self::assertStringContainsString('Illuminate\Foundation\Http\FormRequest', $source, $file);
        }
    }

    public function test_form_requests_do_not_execute_application_use_cases_or_build_http_responses(): void
    {
        foreach ($this->filesIn('Presentation/Http/Requests') as $file) {
            $source = $this->source($file);
            $this->assertNone($source, [
                'Application\\UseCases\\',
                'app(',
                'HttpResponseException',
                'response()->',
            ], $file);
        }
    }

    public function test_checkout_guest_policy_is_reached_through_a_domain_port(): void
    {
        $root = dirname(__DIR__, 2);
        $request = $this->source($root.'/app/Modules/Order/Presentation/Http/Requests/CheckoutRequest.php');
        $useCase = $this->source($root.'/app/Modules/Order/Application/UseCases/Checkout.php');
        $provider = $this->source($root.'/app/Modules/Order/OrderServiceProvider.php');

        self::assertStringNotContainsString('GetSetting', $request);
        self::assertStringContainsString('CheckoutPolicyInterface', $useCase);
        self::assertStringContainsString('CheckoutPolicyInterface::class => EloquentCheckoutPolicy::class', $provider);
    }

    public function test_domain_contracts_are_interfaces_and_infrastructure_implements_them(): void
    {
        foreach ($this->filesIn('Domain/Contracts') as $file) {
            self::assertMatchesRegularExpression('/\binterface\s+\w+/', $this->source($file), $file);
        }

        foreach ($this->filesIn('Infrastructure') as $file) {
            $source = $this->source($file);
            self::assertStringNotContainsString('Application\\', $source, $file);
            self::assertStringNotContainsString('Presentation\\', $source, $file);
        }
    }

    public function test_domain_exceptions_are_the_only_business_failure_boundary(): void
    {
        foreach ($this->filesIn('Domain/Exceptions') as $file) {
            $source = $this->source($file);
            self::assertMatchesRegularExpression('/(?:extends|implements)\s+(?:[A-Za-z_][A-Za-z0-9_]*|\\\\[A-Za-z_][A-Za-z0-9_\\\\]*)/', $source, $file);
            self::assertDoesNotMatchRegularExpression('/response\s*\(|JsonResponse|abort\s*\(/', $source, $file);
        }
    }

    public function test_central_error_handler_is_registered_in_bootstrap(): void
    {
        $bootstrap = file_get_contents(dirname(__DIR__, 2).'/bootstrap/app.php');
        self::assertIsString($bootstrap);
        self::assertStringContainsString('withExceptions', $bootstrap);
        self::assertStringContainsString('DomainAuthenticationException', $bootstrap);
        self::assertStringContainsString('BusinessRuleException', $bootstrap);
        self::assertStringContainsString('ValidationException', $bootstrap);
        self::assertStringContainsString('An internal server error occurred.', $bootstrap);
    }

    /** @return list<string> */
    private function filesIn(string $suffix): array
    {
        $root = dirname(__DIR__, 2).'/app/Modules';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $normalizedSuffix = '/'.trim($suffix, '/').'/';
            if (str_contains($path, $normalizedSuffix)) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function source(string $file): string
    {
        $source = file_get_contents($file);
        self::assertIsString($source, $file);

        return $source;
    }

    /** @return list<string> */
    private function publicActions(string $source): array
    {
        preg_match_all('/public function\s+\w+\s*\((.*?)\)\s*:/s', $source, $matches);

        return $matches[1] ?? [];
    }

    /** @param list<string> $forbidden */
    private function assertNone(string $source, array $forbidden, string $file): void
    {
        foreach ($forbidden as $token) {
            self::assertStringNotContainsString($token, $source, $file.' contains forbidden dependency: '.$token);
        }
    }
}
