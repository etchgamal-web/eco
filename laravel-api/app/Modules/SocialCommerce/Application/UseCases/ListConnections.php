<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;

final class ListConnections
{
    public function __construct(private readonly SocialConnectionRepositoryInterface $connections) {}

    public function execute(array $filters = []): array
    {
        return $this->connections->list($filters);
    }
}
