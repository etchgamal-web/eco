<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class ListSettlements
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(array $filters = []): mixed
    {
        return $this->repo->paginate($filters);
    }
}
