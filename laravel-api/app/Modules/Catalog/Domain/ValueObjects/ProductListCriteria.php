<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class ProductListCriteria
{
    public function __construct(
        public ?string $search = null,
        public ?int $categoryId = null,
        public ?int $brandId = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public string $sort = 'newest',
        public int $perPage = 20,
        public int $page = 1,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            type: $data['type'] ?? null,
            status: $data['status'] ?? null,
            minPrice: isset($data['min_price']) ? (int) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (int) $data['max_price'] : null,
            sort: (string) ($data['sort'] ?? 'newest'),
            perPage: min(100, max(1, (int) ($data['per_page'] ?? 20))),
            page: max(1, (int) ($data['page'] ?? 1)),
        );
    }
}
