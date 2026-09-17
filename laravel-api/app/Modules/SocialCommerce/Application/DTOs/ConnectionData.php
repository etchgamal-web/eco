<?php

namespace App\Modules\SocialCommerce\Application\DTOs;

final readonly class ConnectionData
{
    public function __construct(public string $channel, public string $name, public ?string $providerAccountId, public ?string $accessToken, public ?string $webhookSecret, public array $metadata = []) {}

    public static function fromArray(array $data): self
    {
        return new self($data['channel'], $data['name'], $data['provider_account_id'] ?? null, $data['access_token'] ?? null, $data['webhook_secret'] ?? null, $data['metadata'] ?? []);
    }
}
