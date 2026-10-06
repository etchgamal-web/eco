<?php

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

final class ListPublicCategories
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(): Collection
    {
        return collect($this->categories->all())
            ->filter(static fn (object $category): bool => (bool) $category->is_active)
            ->map(static fn (object $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'products_count' => $category->products_count,
            ])
            ->values();
    }
}
