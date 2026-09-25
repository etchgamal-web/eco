<?php

namespace App\Modules\Catalog\Domain\Contracts;

interface ProductImportReaderInterface
{
    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function rows(string $path, ?string $extension = null): iterable;
}
