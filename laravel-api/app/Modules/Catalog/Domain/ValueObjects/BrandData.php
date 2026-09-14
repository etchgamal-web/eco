<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class BrandData
{
    public function __construct(public string $name, public ?string $slug, public string $status) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['slug'] ?? null, $data['status']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name, 'slug' => $this->slug, 'status' => $this->status];
    }
}
