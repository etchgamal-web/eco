<?php
namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ControllerArchitectureTest extends TestCase
{
    #[DataProvider('controllerProvider')]
    public function test_controllers_do_not_depend_on_repositories(string $controller): void
    {
        $source = file_get_contents($controller);

        self::assertIsString($source);
        self::assertStringNotContainsString('Domain\\Contracts', $source);
        self::assertStringNotContainsString('RepositoryInterface', $source);
        self::assertDoesNotMatchRegularExpression('/private readonly .*Repository/', $source);
    }

    /** @return array<string, array{string}> */
    public static function controllerProvider(): array
    {
        $root = dirname(__DIR__, 2).'/app/Modules';
        $controllers = array_merge(
            glob($root.'/Auth/Presentation/Http/Controllers/*.php') ?: [],
            glob($root.'/Catalog/Presentation/Http/Controllers/*.php') ?: [],
            glob($root.'/Settings/Presentation/Http/Controllers/*.php') ?: [],
            glob($root.'/Order/Presentation/Http/Controllers/*.php') ?: [],
        );

        $cases = [];
        foreach ($controllers as $controller) {
            $cases[basename($controller)] = [$controller];
        }

        return $cases;
    }
}
