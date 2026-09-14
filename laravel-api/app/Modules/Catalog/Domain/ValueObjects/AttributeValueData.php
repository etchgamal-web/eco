<?php

namespace App\Modules\Catalog\Domain\ValueObjects;

final readonly class AttributeValueData
{
    public function __construct(public int $attributeId, public string $value) {}

    public static function fromArray(array $data): self
    {
        return new self((int) $data['attribute_id'], $data['value']);
    }

    public function toArray(): array
    {
        return ['attribute_id' => $this->attributeId, 'value' => $this->value];
    }
}
