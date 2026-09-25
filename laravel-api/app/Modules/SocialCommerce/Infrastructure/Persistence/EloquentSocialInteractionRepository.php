<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Modules\SocialCommerce\Infrastructure\Models\SocialConversation;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialInteraction;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialMessage;
use App\Modules\SocialCommerce\Infrastructure\Models\SocialWebhookEvent;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;

final class EloquentSocialInteractionRepository implements SocialInteractionRepositoryInterface
{
    public function list(array $filters = []): array
    {
        $q = SocialInteraction::query()->with(['customer:id,name,email,phone', 'product:id,name']);
        foreach (['channel', 'customer_id', 'product_id', 'interaction_type', 'status'] as $key) {
            if (isset($filters[$key])) {
                $q->where($key, $filters[$key]);
            }
        }if (isset($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }if (isset($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        return $q->latest()->get()->all();
    }

    public function create(array $data): object
    {
        return SocialInteraction::query()->create($data);
    }

    public function find(int $id): object
    {
        return SocialInteraction::query()->findOrFail($id);
    }

    public function findByIdempotencyKey(string $key): ?object
    {
        return SocialInteraction::query()->where('idempotency_key', $key)->first();
    }

    public function updateInteractionStatus(int $id, string $status): void
    {
        SocialInteraction::query()->whereKey($id)->update(['status' => $status]);
    }

    public function updateMessage(int $id, array $data): void
    {
        SocialMessage::query()->whereKey($id)->update($data);
    }

    public function updateInteraction(int $id, array $data): void
    {
        SocialInteraction::query()->whereKey($id)->update($data);
    }

    public function findConversation(int $id): object
    {
        return SocialConversation::query()->findOrFail($id);
    }

    public function findOrCreateConversation(array $data): object
    {
        return SocialConversation::query()->firstOrCreate(['channel' => $data['channel'], 'provider_conversation_id' => $data['provider_conversation_id']], $data);
    }

    public function addMessage(array $data): object
    {
        return SocialMessage::query()->create($data);
    }

    public function findMessageByIdempotencyKey(string $key): ?object
    {
        return SocialMessage::query()->where('idempotency_key', $key)->first();
    }

    public function findMessage(int $id): ?object
    {
        return SocialMessage::query()->find($id);
    }

    public function updateMessageStatus(int $id, string $status): void
    {
        SocialMessage::query()->whereKey($id)->update(['status' => $status]);
    }

    public function messages(object $conversation): array
    {
        return SocialMessage::query()->where('conversation_id', $conversation->id)->oldest()->get()->all();
    }

    public function updateConversation(object $conversation, array $data): object
    {
        $conversation->fill($data);
        $conversation->save();

        return $conversation;
    }

    public function findWebhookEvent(string $channel, string $providerEventId): ?object
    {
        return SocialWebhookEvent::query()->where('channel', $channel)->where('provider_event_id', $providerEventId)->first();
    }

    public function recordWebhookEvent(array $data): object
    {
        return SocialWebhookEvent::query()->create($data);
    }

    public function markWebhookProcessed(object $event): void
    {
        $event->update(['status' => 'processed', 'processed_at' => now()]);
    }
}
