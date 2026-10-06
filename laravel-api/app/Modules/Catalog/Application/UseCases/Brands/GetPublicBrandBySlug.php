<?php

namespace App\Modules\Catalog\Application\UseCases\Brands;

use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;

final class GetPublicBrandBySlug
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    public function execute(string $slug): array
    {
        $brand = $this->brands->findPublicBySlug($slug);

        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'products_count' => $brand->products_count,
        ];
    }
}
