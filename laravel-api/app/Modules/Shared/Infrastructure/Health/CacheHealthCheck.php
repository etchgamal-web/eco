<?php

namespace App\Modules\Shared\Infrastructure\Health;

use App\Modules\Shared\Domain\Contracts\HealthCheckInterface;
use Illuminate\Support\Facades\Cache;

final class CacheHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'cache';
    }

    public function check(): bool
    {
        $key = 'readiness.'.bin2hex(random_bytes(8));
        Cache::put($key, true, 5);
        $healthy = Cache::get($key) === true;
        Cache::forget($key);

        return $healthy;
    }
}
