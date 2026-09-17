<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Application\DTOs\ConnectionData;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;

final class CreateSocialConnection
{
    public function __construct(private readonly SocialConnectionRepositoryInterface $connections) {}

    public function execute(ConnectionData $data): object
    {
        return $this->connections->create([
            'channel' => $data->channel,
            'name' => $data->name,
            'provider_account_id' => $data->providerAccountId,
            'access_token' => $data->accessToken,
            'webhook_secret' => $data->webhookSecret,
            'metadata' => $data->metadata,
            'is_active' => true,
        ]);
    }
}
