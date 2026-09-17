<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;

final class SetConversationMode
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly GetSocialConversation $getConversation,
    ) {}

    public function execute(int $id, string $mode): object
    {
        return $this->interactions->updateConversation($this->getConversation->execute($id), ['mode' => $mode]);
    }
}
