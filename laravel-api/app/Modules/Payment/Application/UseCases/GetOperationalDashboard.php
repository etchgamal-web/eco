<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;

final class GetOperationalDashboard
{
    public function __construct(private readonly OperationalDashboardReaderInterface $reader)
    {
    }

    public function execute(): array
    {
        return $this->reader->read();
    }
}
