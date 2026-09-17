<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\ConversationNotFoundException;

final class GetSocialConversation
{
    public function __construct(private readonly SocialInteractionRepositoryInterface $interactions) {}

    public function execute(int $id): object
    {
        try {
            return $this->interactions->findConversation($id);
        } catch (\Throwable $e) {
            throw new ConversationNotFoundException('Conversation not found.', 0, $e);
        }
    }
}
