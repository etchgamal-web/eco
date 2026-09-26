<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialSummaryQueryInterface;

final class GetSocialSummary
{
    public function __construct(private readonly SocialSummaryQueryInterface $query) {}

    /** @return array<string, int> */
    public function execute(): array
    {
        return $this->query->summary();
    }
}
