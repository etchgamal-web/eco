<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class GetSettlementSettings
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(): array
    {
        return $this->repo->settings();
    }
}
