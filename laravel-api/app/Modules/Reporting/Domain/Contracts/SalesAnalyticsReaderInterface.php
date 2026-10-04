<?php

namespace App\Modules\Reporting\Domain\Contracts;

interface SalesAnalyticsReaderInterface
{
    /** @return array<string, mixed> */
    public function read(string $from, string $to): array;
}
