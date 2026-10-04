<?php

namespace App\Modules\Integration\Application\UseCases;

use App\Modules\Integration\Domain\Contracts\IntegrationEventRepositoryInterface;

final class RetryIntegrationEvent
{
    public function __construct(private readonly IntegrationEventRepositoryInterface $events) {}
    public function execute(string $source, int $id): array { return $this->events->retry($source, $id); }
}
