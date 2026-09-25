<?php

namespace App\Modules\Shared\Infrastructure\Health;

use App\Modules\Shared\Domain\Contracts\HealthCheckInterface;
use Illuminate\Support\Facades\DB;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'database';
    }

    public function check(): bool
    {
        DB::select('select 1');

        return true;
    }
}
