<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SharedArchitectureTest extends TestCase
{
    public function test_shared_outbox_dispatcher_depends_only_on_shared_abstractions(): void
    {
        $file = dirname(__DIR__, 2).'/app/Modules/Shared/Application/Outbox/OutboxEventDispatcher.php';
        $source = file_get_contents($file);

        self::assertIsString($source);
        self::assertStringNotContainsString('App\\Modules\\Payment\\', $source);
        self::assertStringNotContainsString('App\\Modules\\Shipping\\', $source);
        self::assertStringNotContainsString('App\\Modules\\SocialCommerce\\', $source);
        self::assertStringNotContainsString('App\\Modules\\Order\\', $source);
        self::assertStringContainsString('OutboxEventHandlerInterface', $source);
    }

    public function test_business_outbox_handlers_are_owned_by_their_modules(): void
    {
        foreach (['Payment', 'Shipping', 'SocialCommerce'] as $module) {
            $path = dirname(__DIR__, 2).'/app/Modules/'.$module.'/Application/Outbox';
            self::assertDirectoryExists($path);
            self::assertNotEmpty(glob($path.'/*OutboxHandler.php') ?: []);
        }
    }
}
