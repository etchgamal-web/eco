<?php

namespace App\Modules\Integration\Application\UseCases;

use App\Modules\Integration\Domain\Contracts\IntegrationEventRepositoryInterface;

final class ListIntegrationEvents
{
    public function __construct(private readonly IntegrationEventRepositoryInterface $events) {}
    public function execute(array $filters): array { return $this->events->list($filters); }
}
