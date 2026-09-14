<?php

namespace App\Modules\Customer\Domain\ValueObjects;

final readonly class UpdateCustomerData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
        );
    }
}
