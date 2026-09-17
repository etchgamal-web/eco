<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;

final class ListInteractions
{
    public function __construct(private readonly SocialInteractionRepositoryInterface $interactions) {}

    public function execute(array $filters = []): array
    {
        return $this->interactions->list($filters);
    }
}
