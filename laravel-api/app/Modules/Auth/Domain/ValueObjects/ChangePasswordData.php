<?php

namespace App\Modules\Auth\Domain\ValueObjects;

final readonly class ChangePasswordData
{
    public function __construct(
        public string $currentPassword,
        public string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self($data['current_password'], $data['password']);
    }
}
