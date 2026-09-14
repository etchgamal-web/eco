<?php

namespace App\Modules\Staff\Domain\ValueObjects;

final readonly class StaffData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $status,
        public array $roleSlugs = [],
        public ?string $password = null,
        public bool $rolesProvided = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            (string) ($data['status'] ?? 'active'),
            array_values($data['roles'] ?? []),
            $data['password'] ?? null,
            array_key_exists('roles', $data),
        );
    }
}
