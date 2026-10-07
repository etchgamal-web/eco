<?php

namespace App\Modules\Content\Infrastructure\Persistence;

use App\Modules\Content\Domain\Contracts\ContentRepositoryInterface;
use App\Modules\Content\Infrastructure\Models\ContentItem;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class EloquentContentRepository implements ContentRepositoryInterface
{
    public function listPublic(?string $type = null): iterable
    {
        return $this->publicQuery($type)->latest('published_at')->get();
    }

    public function findPublicBySlug(string $slug): object
    {
        return $this->publicQuery()->where('slug', $slug)->firstOrFail();
    }

    public function listAdmin(array $filters = []): iterable
    {
        return ContentItem::query()
            ->with($this->relations())
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->latest('updated_at')->get();
    }

    public function find(int $id): object
    {
        return ContentItem::query()->with($this->relations())->findOrFail($id);
    }

    public function save(array $data, ?int $id = null): object
    {
        $item = $id === null ? new ContentItem() : $this->find($id);
        $item->fill($data);
        if (($data['status'] ?? $item->status) === 'published' && $item->published_at === null) {
            $item->published_at = now();
        }
        if (($data['status'] ?? $item->status) !== 'published') {
            $item->published_at = $data['published_at'] ?? null;
        }
        $item->save();
        $item->products()->sync($this->orderedIds($data['product_ids'] ?? []));
        $item->categories()->sync($this->orderedIds($data['category_ids'] ?? []));
        return $item->load($this->relations());
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }

    public function publish(int $id): object
    {
        $item = $this->find($id);
        $item->forceFill(['status' => 'published', 'published_at' => $item->published_at ?? now()])->save();
        return $item->load($this->relations());
    }

    public function unpublish(int $id): object
    {
        $item = $this->find($id);
        $item->forceFill(['status' => 'draft', 'published_at' => null])->save();
        return $item->load($this->relations());
    }

    private function publicQuery(?string $type = null): Builder
    {
        return ContentItem::query()->published()->with([
            'author:id,name',
            'parent:id,title,slug,type',
            'faqs' => fn ($q) => $q->published()->select(['id', 'parent_id', 'type', 'title', 'slug', 'excerpt', 'body', 'status', 'published_at']),
            'products' => fn ($q) => $q->where('products.status', 'active')->with(['brand:id,name,slug', 'category:id,name,slug']),
            'categories' => fn ($q) => $q->where('categories.is_active', true),
        ])->when($type, fn (Builder $q, string $value) => $q->where('type', $value));
    }

    private function relations(): array
    {
        return ['author:id,name', 'parent:id,title,slug,type', 'faqs', 'products', 'categories'];
    }

    private function orderedIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return collect($ids)->mapWithKeys(fn (int $id, int $index) => [$id => ['sort_order' => $index]])->all();
    }
}
