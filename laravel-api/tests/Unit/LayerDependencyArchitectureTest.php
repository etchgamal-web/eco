<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class LayerDependencyArchitectureTest extends TestCase
{
    public function test_domain_does_not_depend_on_application_or_infrastructure(): void
    {
        $files = $this->phpFilesIn('Domain');
        foreach ($files as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringNotContainsString('Application\\', $source, $file);
            self::assertStringNotContainsString('Infrastructure\\', $source, $file);
            self::assertStringNotContainsString('Illuminate\\Database\\', $source, $file);
        }
    }

    public function test_controllers_do_not_depend_on_domain_contracts_or_infrastructure(): void
    {
        $files = $this->phpFilesIn('Presentation/Http/Controllers');
        foreach ($files as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringNotContainsString('Domain\\Contracts', $source, $file);
            self::assertStringNotContainsString('RepositoryInterface', $source, $file);
            self::assertStringNotContainsString('Infrastructure\\', $source, $file);
            self::assertStringNotContainsString('App\\Models', $source, $file);
        }
    }

    /** @return list<string> */
    private function phpFilesIn(string $suffix): array
    {
        $root = dirname(__DIR__, 2) . '/app/Modules';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getPathname(), '/' . $suffix . '/' . $file->getFilename()) || ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getPathname(), '/' . $suffix . '/'))) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
