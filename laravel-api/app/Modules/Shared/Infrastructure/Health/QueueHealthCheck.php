<?php

namespace App\Modules\Shared\Infrastructure\Health;

use App\Modules\Shared\Domain\Contracts\HealthCheckInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class QueueHealthCheck implements HealthCheckInterface
{
    public function name(): string
    {
        return 'queue';
    }

    public function check(): bool
    {
        $connection = (string) config('queue.default');

        if (in_array($connection, ['sync', 'null', 'deferred', 'background'], true)) {
            return true;
        }

        if ($connection === 'database') {
            return DB::getSchemaBuilder()->hasTable((string) config('queue.connections.database.table', 'jobs'));
        }

        if ($connection === 'redis') {
            return (string) Redis::connection(config('queue.connections.redis.connection', 'default'))->ping() === 'PONG';
        }

        return false;
    }
}
