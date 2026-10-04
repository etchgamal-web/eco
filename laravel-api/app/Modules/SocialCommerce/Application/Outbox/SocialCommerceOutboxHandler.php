<?php

namespace App\Modules\SocialCommerce\Application\Outbox;

use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Exceptions\AmbiguousExternalResultException;
use RuntimeException;

final class SocialCommerceOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly SocialInteractionRepositoryInterface $interactions,
        private readonly SocialConnectionRepositoryInterface $connections,
        private readonly SocialMessagingProviderInterface $provider,
        private readonly OutboxRepositoryInterface $outbox,
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
            if (! $message || $message->status === 'sent' || in_array((string) ($message->operation_status ?? ''), ['sent', 'ambiguous', 'failed'], true)) {
                if ($message?->operation_status === 'ambiguous') {
                    $this->outbox->markAmbiguous((int) $event->id, (string) $event->claim_token, (string) ($message->operation_last_error ?? 'Social operation is ambiguous.'));

                    return;
                }
                $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

                return;
            }
            $connection = $this->connections->activeForChannel((string) ($payload['channel'] ?? ''));
            if (! $connection) {
                throw new RuntimeException('No active social connection for queued message.');
            }
            $operationToken = (string) $event->claim_token;
            $this->interactions->startOperation($type, (int) $event->aggregate_id, (string) ($event->deduplication_key ?? $event->id));
            if (! $this->interactions->acquireOperationLease($type, (int) $event->aggregate_id, $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
                return;
            }
            $this->interactions->updateMessageStatus((int) $event->aggregate_id, 'processing');
            $result = $this->provider->sendMessage($connection, (string) $payload['recipient'], (string) $payload['body']);
            if (! $this->outbox->ownsClaim((int) $event->id, $operationToken) || ! $this->interactions->ownsOperationLease($type, (int) $event->aggregate_id, $operationToken)) {
                return;
            }
            $this->interactions->completeOperation($type, (int) $event->aggregate_id, $operationToken, [
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'metadata' => $result,
            ]);
        } else {
            $interaction = $this->interactions->find((int) $event->aggregate_id);
            if (! $interaction || $interaction->status === 'sent' || in_array((string) ($interaction->operation_status ?? ''), ['sent', 'ambiguous', 'failed'], true)) {
                if ($interaction?->operation_status === 'ambiguous') {
                    $this->outbox->markAmbiguous((int) $event->id, (string) $event->claim_token, (string) ($interaction->operation_last_error ?? 'Social operation is ambiguous.'));

                    return;
                }
                $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

                return;
            }
            $connection = $this->connections->activeForChannel((string) ($payload['channel'] ?? ''));
            if (! $connection) {
                throw new RuntimeException('No active social connection for queued comment.');
            }
            $operationToken = (string) $event->claim_token;
            $this->interactions->startOperation($type, (int) $event->aggregate_id, (string) ($event->deduplication_key ?? $event->id));
            if (! $this->interactions->acquireOperationLease($type, (int) $event->aggregate_id, $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
                return;
            }
            $this->interactions->updateInteractionStatus((int) $event->aggregate_id, 'processing');
            $result = $this->provider->replyToComment($connection, (string) $payload['comment_id'], (string) $payload['body']);
            if (! $this->outbox->ownsClaim((int) $event->id, $operationToken) || ! $this->interactions->ownsOperationLease($type, (int) $event->aggregate_id, $operationToken)) {
                return;
            }
            $this->interactions->completeOperation($type, (int) $event->aggregate_id, $operationToken, [
                'provider_interaction_id' => $result['provider_message_id'] ?? null,
                'metadata' => array_merge((array) ($interaction->metadata ?? []), $result),
            ]);
        }
        $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        if ($exception instanceof AmbiguousExternalResultException) {
            $this->interactions->failOperation((string) $event->event_type, (int) $event->aggregate_id, (string) $event->claim_token, 'ambiguous', $exception->getMessage());
            $this->outbox->markAmbiguous((int) $event->id, (string) $event->claim_token, $exception->getMessage());

            return;
        }
        $exhausted = $this->outbox->markFailed((int) $event->id, (string) $event->claim_token, $exception->getMessage());
        $this->interactions->failOperation((string) $event->event_type, (int) $event->aggregate_id, (string) $event->claim_token, $exhausted ? 'failed' : 'processing', $exception->getMessage());
    }
}
