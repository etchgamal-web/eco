<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\ConversationNotFoundException;

final class SendConversationMessage
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly SocialMessagingProviderInterface $provider,
        private readonly GetSocialConversation $getConversation,
        private readonly AuthenticationServiceInterface $authentication,
    ) {}

    public function execute(int $id, string $body): object
    {
        $conversation = $this->getConversation->execute($id);
        $connection = $this->connections->activeForChannel($conversation->channel);
        if (! $connection) {
            throw new ConversationNotFoundException('No active social connection is configured for this channel.');
        }

        $result = $this->provider->sendMessage($connection, (string) $conversation->provider_customer_id, $body);
        $actor = $this->authentication->user();
        $responder = ['type' => 'human', 'id' => $actor?->id, 'name' => $actor?->name ?? 'Human Operator'];

        return $this->interactions->addMessage([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender' => 'store',
            'responder_type' => $responder['type'],
            'responder_id' => $responder['id'],
            'responder_name' => $responder['name'],
            'provider_message_id' => $result['provider_message_id'] ?? null,
            'body' => $body,
            'metadata' => array_merge($result, ['responder' => $responder]),
        ]);
    }
}
