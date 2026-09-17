<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;

final class ListConversationMessages
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly GetSocialConversation $getConversation,
    ) {}

    public function execute(int $id): array
    {
        return $this->interactions->messages($this->getConversation->execute($id));
    }
}
