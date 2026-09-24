<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class ImportSettlement
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(string $path, string $provider, ?string $from, ?string $to): mixed
    {
        return $this->repo->import($path, $provider, $from, $to);
    }
}
