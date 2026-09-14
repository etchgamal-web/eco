<?php

namespace App\Modules\Payment\Infrastructure\Resilience;

use App\Models\ProviderCircuitBreaker as ProviderCircuitState;
use RuntimeException;

final class ProviderCircuitBreaker
{
    public function __construct(private readonly int $failureThreshold = 3, private readonly int $openSeconds = 60)
    {
    }

    public function call(string $provider, callable $operation): mixed
    {
        $state = ProviderCircuitState::query()->firstOrCreate(['provider' => $provider]);
        if ($state->opened_until?->isFuture()) {
            throw new RuntimeException("Provider circuit is open: {$provider}");
        }

        try {
            $result = $operation();
            $state->forceFill(['failure_count' => 0, 'opened_until' => null])->save();
            return $result;
        } catch (\Throwable $exception) {
            $failures = ((int) $state->failure_count) + 1;
            $state->forceFill([
                'failure_count' => $failures,
                'last_failure_at' => now(),
                'opened_until' => $failures >= $this->failureThreshold ? now()->addSeconds($this->openSeconds) : null,
            ])->save();
            throw $exception;
        }
    }
}
