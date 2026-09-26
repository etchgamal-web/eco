<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class ProductData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
        public string $type,
        public string $status,
        public int $price,
        public ?int $brandId,
        public ?int $categoryId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'], $data['slug'] ?? null, $data['description'] ?? null,
            $data['type'], $data['status'], (int) ($data['price'] ?? 0), $data['brand_id'] ?? null, $data['category_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description,
            'type' => $this->type, 'status' => $this->status, 'price' => $this->price,
            'brand_id' => $this->brandId, 'category_id' => $this->categoryId,
        ];
    }
}
