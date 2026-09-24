<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class GetSettlement
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(int $id): mixed
    {
        return $this->repo->show($id);
    }
}
