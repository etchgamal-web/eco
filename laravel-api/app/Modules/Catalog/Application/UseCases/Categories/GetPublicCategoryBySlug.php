<?php

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;

final class GetPublicCategoryBySlug
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(string $slug): array
    {
        $category = $this->categories->findPublicBySlug($slug);

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'products_count' => $category->products_count,
        ];
    }
}
