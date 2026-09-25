<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class ExportSettlementItems
{
    public function __construct(private readonly SettlementRepositoryInterface $settlements) {}

    public function execute(int $id): iterable
    {
        return $this->settlements->exportItems($id);
    }
}
