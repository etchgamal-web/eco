<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class CaptureLandingLead
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function execute(string $slug, array $data): object
    {
        $page = $this->pages->bySlug($slug, true);

        return $this->pages->addLead($page->id, $data);
    }
}
