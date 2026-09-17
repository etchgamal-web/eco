<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Application\DTOs\ConnectionData;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;

final class UpdateSocialConnection
{
    public function __construct(
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly GetSocialConnection $getConnection,
    ) {}

    public function execute(int $id, ConnectionData $data): object
    {
        return $this->connections->update($this->getConnection->execute($id), [
            'channel' => $data->channel,
            'name' => $data->name,
            'provider_account_id' => $data->providerAccountId,
            'access_token' => $data->accessToken,
            'webhook_secret' => $data->webhookSecret,
            'metadata' => $data->metadata,
        ]);
    }
}
