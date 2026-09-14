<?php

namespace App\Modules\Shared\Domain\Contracts;

interface OutboxEventRepositoryInterface
{
    public function markDispatched(string $deduplicationKey): void;

    public function markFailed(string $deduplicationKey, string $error): void;
}
