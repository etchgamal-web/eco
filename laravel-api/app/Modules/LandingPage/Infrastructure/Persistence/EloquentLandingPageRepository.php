<?php

namespace App\Modules\LandingPage\Infrastructure\Persistence;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;
use App\Modules\LandingPage\Infrastructure\Models\LandingPage;
use App\Modules\LandingPage\Infrastructure\Models\LandingPageEvent;
use App\Modules\LandingPage\Infrastructure\Models\LandingPageLead;

final class EloquentLandingPageRepository implements LandingPageRepositoryInterface
{
    public function list(array $filters = []): array
    {
        return LandingPage::query()->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))->latest()->get()->all();
    }

    public function find(int $id): object
    {
        return LandingPage::query()->findOrFail($id);
    }

    public function bySlug(string $slug, bool $publishedOnly = true): object
    {
        return LandingPage::query()->where('slug', $slug)->when($publishedOnly, fn ($q) => $q->where('status', 'published'))->firstOrFail();
    }

    public function save(array $data, ?int $id = null): object
    {
        $page = $id ? $this->find($id) : new LandingPage;
        $page->fill($data);
        $page->save();

        return $page;
    }

    public function delete(object $page): void
    {
        $page->delete();
    }

    public function addLead(int $pageId, array $data): object
    {
        return LandingPageLead::query()->create(array_merge($data, ['landing_page_id' => $pageId]));
    }

    public function recentLeadByKey(int $pageId, string $key, int $hours = 24): ?object
    {
        return LandingPageLead::query()->where('landing_page_id', $pageId)->where('dedupe_key', $key)
            ->where('created_at', '>=', now()->subHours($hours))->latest('id')->first();
    }

    public function listLeads(array $filters = []): array
    {
        return LandingPageLead::query()->with(['page:id,title,slug'])
            ->when(isset($filters['landing_page_id']), fn ($q) => $q->where('landing_page_id', $filters['landing_page_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->latest()->get()->all();
    }

    public function updateLead(int $id, array $data): object
    {
        $lead = LandingPageLead::query()->findOrFail($id);
        $lead->fill($data)->save();

        return $lead->load('page:id,title,slug');
    }

    public function recordEvent(int $pageId, array $data): object
    {
        return LandingPageEvent::query()->create(array_merge($data, ['landing_page_id' => $pageId]));
    }

    public function eventExists(int $pageId, string $key): bool
    {
        return LandingPageEvent::query()->where('landing_page_id', $pageId)->where('dedupe_key', $key)->exists();
    }

    public function eventByKey(int $pageId, string $key): ?object
    {
        return LandingPageEvent::query()->where('landing_page_id', $pageId)->where('dedupe_key', $key)->first();
    }

    public function stats(int $pageId, array $filters = []): array
    {
        $query = LandingPageEvent::query()->where('landing_page_id', $pageId);
        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        $events = $query->get();

        return ['page_id' => $pageId, 'views' => $events->where('event_type', 'view')->count(), 'conversions' => $events->where('event_type', 'conversion')->count(), 'leads' => LandingPageLead::query()->where('landing_page_id', $pageId)->count(), 'by_source' => $events->groupBy('source')->map->count()->toArray()];
    }
}
