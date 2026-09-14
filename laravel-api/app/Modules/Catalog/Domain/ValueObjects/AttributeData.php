<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class AttributeData
{
    public function __construct(public string $name) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
