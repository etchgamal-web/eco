<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Models\OutboxEvent;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\ConversationNotFoundException;
use Illuminate\Support\Facades\DB;

final class SendConversationMessage
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly GetSocialConversation $getConversation,
        private readonly AuthenticationServiceInterface $authentication,
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

        return DB::transaction(function () use ($conversation, $body, $actor, $key): object {
            $existing = \App\Models\SocialMessage::query()->where('idempotency_key', $key)->first();
            if ($existing) return $existing;
            $responder = ['type' => 'human', 'id' => $actor?->id, 'name' => $actor?->name ?? 'Human Operator'];
            $message = $this->interactions->addMessage([
                'conversation_id' => $conversation->id, 'direction' => 'outbound', 'sender' => 'store',
                'status' => 'pending', 'idempotency_key' => $key,
                'responder_type' => $responder['type'], 'responder_id' => $responder['id'],
                'responder_name' => $responder['name'], 'body' => $body, 'metadata' => ['responder' => $responder],
            ]);
            OutboxEvent::query()->create([
                'aggregate_type' => 'social_message', 'aggregate_id' => $message->id,
                'event_type' => 'social.message.send', 'deduplication_key' => $key, 'status' => 'pending',
                'payload' => ['channel' => $conversation->channel, 'recipient' => $conversation->provider_customer_id, 'body' => $body],
            ]);
            return $message;
        });
    }
}
