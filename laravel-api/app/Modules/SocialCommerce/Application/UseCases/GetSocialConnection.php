<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialConnectionNotFoundException;

final class GetSocialConnection
{
    public function __construct(private readonly SocialConnectionRepositoryInterface $connections) {}

    public function execute(int $id): object
    {
        try {
            return $this->connections->find($id);
        } catch (\Throwable $e) {
            throw new SocialConnectionNotFoundException('Social connection not found.', 0, $e);
        }
    }
}
