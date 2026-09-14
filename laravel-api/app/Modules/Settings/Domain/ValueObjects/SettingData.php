<?php

namespace App\Modules\Settings\Domain\ValueObjects;

final readonly class SettingData
{
    public function __construct(
        public string $group,
        public string $key,
        public mixed $value,
        public string $type = 'string',
        public ?string $description = null,
        public bool $isSecret = false,
        public bool $isEncrypted = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['group'], $data['key'], $data['value'],
            $data['type'] ?? 'string', $data['description'] ?? null,
            (bool) ($data['is_secret'] ?? false), (bool) ($data['is_encrypted'] ?? false),
        );
    }

    public static function fromModel(object $setting): self
    {
        return new self(
            $setting->group, $setting->key, $setting->getTypedValue(),
            $setting->type, $setting->description, (bool) ($setting->is_secret ?? false),
            (bool) ($setting->is_encrypted ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'group' => $this->group, 'key' => $this->key,
            'value' => $this->isSecret ? '********' : $this->value,
            'type' => $this->type, 'description' => $this->description,
            'is_secret' => $this->isSecret,
            'is_encrypted' => $this->isEncrypted,
        ];
    }
}
