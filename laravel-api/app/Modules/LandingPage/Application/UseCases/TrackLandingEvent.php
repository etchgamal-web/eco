<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class TrackLandingEvent
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function record(string $slug, array $data, ?string $idempotencyKey = null): object
    {
        $page = $this->pages->bySlug($slug, true);
        $key = $idempotencyKey ?: (isset($data['session_id'])
            ? hash('sha256', implode('|', [$page->id, $data['session_id'], $data['event_type'], $data['source'] ?? '']))
            : null);
        if ($key) {
            $existing = $this->pages->eventByKey($page->id, $key);
            if ($existing) return $existing;
            $data['dedupe_key'] = $key;
        }

        return $this->pages->recordEvent($page->id, $data);
    }

    public function stats(int $pageId, array $filters = []): array
    {
        return $this->pages->stats($pageId, $filters);
    }
}
