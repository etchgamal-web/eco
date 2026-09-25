<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class ReplyToSocialComment
{
    public function __construct(private readonly SocialInteractionRepositoryInterface $interactions, private readonly SocialConnectionRepositoryInterface $connections, private readonly TransactionManagerInterface $transactions, private readonly AuthenticationServiceInterface $authentication, private readonly OutboxEventRepositoryInterface $outbox) {}

    public function execute(int $interactionId, string $body, ?string $idempotencyKey = null): object
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
        $actor = $this->authentication->user();
        $key = $idempotencyKey ?: 'social:comment-reply:'.hash('sha256', implode('|', [$interaction->id, $body, $actor?->id ?? 'system']));
        return $this->transactions->run(function () use ($interaction, $body, $actor, $commentId, $connection, $key): object {
            $existing = $this->interactions->find($interaction->id);
            $duplicate = $this->interactions->findByIdempotencyKey($key);
            if ($duplicate) return $duplicate;
            $responder = ['type' => 'human', 'id' => $actor?->id, 'name' => $actor?->name ?? 'Human Operator'];
            $reply = $this->interactions->create([
                'channel' => $existing->channel, 'interaction_type' => 'comment_reply', 'conversation_id' => $existing->conversation_id,
                'customer_id' => $existing->customer_id, 'product_id' => $existing->product_id, 'content' => $body,
                'status' => 'pending', 'idempotency_key' => $key, 'responder_type' => $responder['type'],
                'responder_id' => $responder['id'], 'responder_name' => $responder['name'],
                'metadata' => ['provider_comment_id' => $commentId, 'responder' => $responder],
            ]);
            $this->outbox->record('social_comment', (int) $reply->id, 'social.comment.reply', $key, ['channel' => $connection->channel, 'comment_id' => $commentId, 'body' => $body]);
            return $reply;
        });
    }
}
