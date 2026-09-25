<?php

namespace App\Modules\Shared\Infrastructure\Health;

use App\Modules\Shared\Domain\Contracts\HealthCheckInterface;

final class StorageHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'storage';
    }

    public function check(): bool
    {
        return is_writable(storage_path());
    }
}
