<?php

namespace App\Modules\Payment\Domain\Contracts;

interface OperationalDashboardReaderInterface
{
    public function read(): array;
}
