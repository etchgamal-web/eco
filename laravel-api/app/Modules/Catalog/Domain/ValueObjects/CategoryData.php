<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?int $parentId,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'], $data['slug'] ?? null,
            isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            (bool) $data['is_active'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name, 'slug' => $this->slug,
            'parent_id' => $this->parentId, 'is_active' => $this->isActive,
        ];
    }
}
