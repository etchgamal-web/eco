<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class GetPublishedLandingPage
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function execute(string $slug): object
    {
        return $this->pages->bySlug($slug, true);
    }
}
