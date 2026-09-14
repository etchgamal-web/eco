<?php

namespace App\Modules\Auth\Domain\ValueObjects;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            password: $data['password'],
        );
    }
}
