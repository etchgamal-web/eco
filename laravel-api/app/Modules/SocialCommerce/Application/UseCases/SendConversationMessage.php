<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\ConversationNotFoundException;

final class SendConversationMessage
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly TransactionManagerInterface $transactions,
        private readonly GetSocialConversation $getConversation,
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OutboxEventRepositoryInterface $outbox,
    ) {}

    public function execute(int $id, string $body, ?string $idempotencyKey = null): object
    {
        $conversation = $this->getConversation->execute($id);
        $connection = $this->connections->activeForChannel($conversation->channel);
        if (! $connection) {
            throw new ConversationNotFoundException('No active social connection is configured for this channel.');
        }

        $actor = $this->authentication->user();
        $key = $idempotencyKey ?: 'social:message:'.hash('sha256', implode('|', [$conversation->id, $body, $actor?->id ?? 'system']));

        return $this->transactions->run(function () use ($conversation, $body, $actor, $key): object {
            $existing = $this->interactions->findMessageByIdempotencyKey($key);
            if ($existing) {
                return $existing;
            }
            $responder = ['type' => 'human', 'id' => $actor?->id, 'name' => $actor?->name ?? 'Human Operator'];
            $message = $this->interactions->addMessage([
                'conversation_id' => $conversation->id, 'direction' => 'outbound', 'sender' => 'store',
                'status' => 'pending', 'idempotency_key' => $key,
                'responder_type' => $responder['type'], 'responder_id' => $responder['id'],
                'responder_name' => $responder['name'], 'body' => $body, 'metadata' => ['responder' => $responder],
            ]);
            $this->outbox->record('social_message', (int) $message->id, 'social.message.send', $key, ['channel' => $conversation->channel, 'recipient' => $conversation->provider_customer_id, 'body' => $body]);

            return $message;
        });
    }
}
