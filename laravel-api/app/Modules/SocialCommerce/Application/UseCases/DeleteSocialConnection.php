<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;

final class DeleteSocialConnection
{
    public function __construct(
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly GetSocialConnection $getConnection,
    ) {}

    public function execute(int $id): void
    {
        $this->connections->delete($this->getConnection->execute($id));
    }
}
