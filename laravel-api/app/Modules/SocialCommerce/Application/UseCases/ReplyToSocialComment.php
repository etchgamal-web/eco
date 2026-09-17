<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final class ReplyToSocialComment
{
    public function __construct(private readonly SocialInteractionRepositoryInterface $interactions, private readonly SocialConnectionRepositoryInterface $connections, private readonly SocialMessagingProviderInterface $provider, private readonly AuthenticationServiceInterface $authentication) {}

    public function execute(int $interactionId, string $body): object
    {
        $interaction = $this->interactions->find($interactionId);
        if (($interaction->interaction_type ?? null) !== 'comment') {
            throw new SocialCommerceException('Interaction is not a social comment.');
        }
        $commentId = (string) data_get($interaction->metadata, 'provider_comment_id');
        if ($commentId === '') {
            throw new SocialCommerceException('Comment provider ID is missing.');
        }
        $connection = $this->connections->activeForChannel((string) $interaction->channel);
        if (! $connection) {
            throw new SocialCommerceException('No active social connection is configured for this channel.');
        }
        $result = $this->provider->replyToComment($connection, $commentId, $body);
        $actor = $this->authentication->user();
        $responder = ['type' => 'human', 'id' => $actor?->id, 'name' => $actor?->name ?? 'Human Operator'];
        $this->interactions->addMessage(['conversation_id' => $interaction->conversation_id, 'direction' => 'outbound', 'sender' => 'human', 'responder_type' => $responder['type'], 'responder_id' => $responder['id'], 'responder_name' => $responder['name'], 'provider_message_id' => $result['provider_message_id'] ?? null, 'body' => $body, 'metadata' => array_merge($result, ['responder' => $responder])]);

        return $this->interactions->create(['channel' => $interaction->channel, 'interaction_type' => 'comment_reply', 'provider_interaction_id' => $result['provider_message_id'] ?? null, 'conversation_id' => $interaction->conversation_id, 'customer_id' => $interaction->customer_id, 'product_id' => $interaction->product_id, 'content' => $body, 'status' => 'sent', 'responder_type' => $responder['type'], 'responder_id' => $responder['id'], 'responder_name' => $responder['name'], 'metadata' => array_merge($result, ['responder' => $responder])]);
    }
}
