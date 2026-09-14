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
                'Illuminate\\Database\\',
                'Illuminate\\Http\\',
                'Illuminate\\Support\\Facades\\',
            ], $file);
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
            self::assertStringContainsString('Illuminate\\Foundation\\Http\\FormRequest', $source, $file);
        }
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
            self::assertMatchesRegularExpression('/(?:extends|implements)\s+\w+/', $source, $file);
            self::assertDoesNotMatchRegularExpression('/response\s*\(|JsonResponse|abort\s*\(/', $source, $file);
        }
    }

    public function test_central_error_handler_is_registered_in_bootstrap(): void
    {
        $bootstrap = file_get_contents(dirname(__DIR__, 2) . '/bootstrap/app.php');
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
        $root = dirname(__DIR__, 2) . '/app/Modules';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $normalizedSuffix = '/' . trim($suffix, '/') . '/';
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
            self::assertStringNotContainsString($token, $source, $file . ' contains forbidden dependency: ' . $token);
        }
    }
}
