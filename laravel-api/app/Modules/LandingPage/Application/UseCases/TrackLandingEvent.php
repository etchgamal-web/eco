<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class TrackLandingEvent
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function record(string $slug, array $data): object
    {
        $page = $this->pages->bySlug($slug, true);

        return $this->pages->recordEvent($page->id, $data);
    }

    public function stats(int $pageId, array $filters = []): array
    {
        return $this->pages->stats($pageId, $filters);
    }
}
