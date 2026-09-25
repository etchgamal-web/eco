<?php

namespace App\Modules\Shared\Application\Health;

use App\Modules\Shared\Domain\Contracts\HealthCheckInterface;
use Throwable;

final class ReadinessChecker
{
    /**
     * @param  iterable<HealthCheckInterface>  $checks
     */
    public function __construct(private readonly iterable $checks) {}

    /**
     * @return array{ready: bool, checks: array<string, bool>}
     */
    public function check(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            try {
                $results[$check->name()] = $check->check();
            } catch (Throwable) {
                $results[$check->name()] = false;
            }
        }

        return [
            'ready' => ! in_array(false, $results, true),
            'checks' => $results,
        ];
    }
}
