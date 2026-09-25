<?php

namespace App\Modules\SocialCommerce\Application\Outbox;

use App\Modules\Shared\Application\Outbox\OutboxEventHandlerInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use RuntimeException;

final class SocialCommerceOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly SocialMessagingProviderInterface $provider,
        private readonly OutboxEventRepositoryInterface $outbox,
    ) {}

    public function supports(string $eventType): bool
    {
        return in_array($eventType, ['social.message.send', 'social.comment.reply'], true);
    }

    public function handle(object $event): void
    {
        $payload = (array) $event->payload;
        $type = (string) $event->event_type;
        if ($type === 'social.message.send') {
            $message = $this->interactions->findMessage((int) $event->aggregate_id);
            if (! $message || $message->status === 'sent') {
                $this->outbox->markDispatched((string) $event->deduplication_key);

                return;
            }
            $connection = $this->connections->activeForChannel((string) ($payload['channel'] ?? ''));
            if (! $connection) {
                throw new RuntimeException('No active social connection for queued message.');
            }
            $this->interactions->updateMessageStatus((int) $event->aggregate_id, 'processing');
            $result = $this->provider->sendMessage($connection, (string) $payload['recipient'], (string) $payload['body']);
            $this->interactions->updateMessage((int) $event->aggregate_id, [
                'status' => 'sent',
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'metadata' => $result,
            ]);
        } else {
            $interaction = $this->interactions->find((int) $event->aggregate_id);
            if (! $interaction || $interaction->status === 'sent') {
                $this->outbox->markDispatched((string) $event->deduplication_key);

                return;
            }
            $connection = $this->connections->activeForChannel((string) ($payload['channel'] ?? ''));
            if (! $connection) {
                throw new RuntimeException('No active social connection for queued comment.');
            }
            $this->interactions->updateInteractionStatus((int) $event->aggregate_id, 'processing');
            $result = $this->provider->replyToComment($connection, (string) $payload['comment_id'], (string) $payload['body']);
            $this->interactions->updateInteraction((int) $event->aggregate_id, [
                'status' => 'sent',
                'provider_interaction_id' => $result['provider_message_id'] ?? null,
                'metadata' => array_merge((array) ($interaction->metadata ?? []), $result),
            ]);
        }
        $this->outbox->markDispatched((string) $event->deduplication_key);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $exhausted = $this->outbox->markFailed((string) $event->deduplication_key, $exception->getMessage());
        if ($event->event_type === 'social.message.send') {
            $this->interactions->updateMessageStatus((int) $event->aggregate_id, $exhausted ? 'failed' : 'retrying');
        } else {
            $this->interactions->updateInteractionStatus((int) $event->aggregate_id, $exhausted ? 'failed' : 'retrying');
        }
    }
}
