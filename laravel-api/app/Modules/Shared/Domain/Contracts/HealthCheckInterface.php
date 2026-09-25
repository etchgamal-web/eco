<?php

namespace App\Modules\Shared\Domain\Contracts;

interface HealthCheckInterface
{
    public function name(): string;

    public function check(): bool;
}
