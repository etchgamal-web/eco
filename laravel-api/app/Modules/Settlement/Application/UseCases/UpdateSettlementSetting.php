<?php

namespace App\Modules\Settlement\Application\UseCases;

use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;

final class UpdateSettlementSetting
{
    public function __construct(private readonly SettlementRepositoryInterface $repo) {}

    public function execute(string $key, mixed $value): array
    {
        return $this->repo->saveSetting($key, $value);
    }
}
