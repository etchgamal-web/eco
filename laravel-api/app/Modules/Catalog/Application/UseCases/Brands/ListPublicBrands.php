<?php

namespace App\Modules\Catalog\Application\UseCases\Brands;

use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;
use Illuminate\Support\Collection;

final class ListPublicBrands
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    public function execute(): Collection
    {
        return collect($this->brands->all())
            ->filter(static fn (object $brand): bool => $brand->status === 'active')
            ->map(static fn (object $brand): array => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'products_count' => $brand->products_count,
            ])
            ->values();
    }
}
