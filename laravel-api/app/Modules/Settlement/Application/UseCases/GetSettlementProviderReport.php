<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class GetSettlementProviderReport
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(array $filters = []): array
    {
        return $this->repo->providers($filters);
    }
}
