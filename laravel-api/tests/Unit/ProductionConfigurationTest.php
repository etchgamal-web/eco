<?php

namespace Tests\Unit;

use Tests\TestCase;

final class ProductionConfigurationTest extends TestCase
{
    public function test_production_environment_template_disables_debug_and_uses_versioned_webhooks(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/.env.production.example');

        self::assertStringContainsString('APP_ENV=production', $template);
        self::assertStringContainsString('APP_DEBUG=false', $template);
        self::assertStringContainsString('SESSION_SECURE_COOKIE=true', $template);
        self::assertStringContainsString('QUEUE_CONNECTION=redis', $template);
        self::assertStringContainsString('/api/v1/webhooks/paymob', $template);
        self::assertStringContainsString('/api/v1/webhooks/kashier', $template);
        self::assertStringContainsString('/api/v1/webhooks/bosta', $template);
        self::assertStringNotContainsString('/api/webhooks/', $template);
    }

    public function test_production_operations_have_deployment_artifacts(): void
    {
        self::assertFileExists(dirname(__DIR__, 2) . '/deploy/ecommerce-scheduler.cron');
        self::assertFileExists(dirname(__DIR__, 2) . '/deploy/supervisor/ecommerce-worker.conf');
        self::assertTrue(is_executable(dirname(__DIR__, 2) . '/scripts/backup_postgres.sh'));
    }
}
