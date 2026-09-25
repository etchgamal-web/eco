<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface SocialInteractionRepositoryInterface
{
    public function list(array $filters = []): array;

    public function create(array $data): object;

    public function find(int $id): object;

    public function findByIdempotencyKey(string $key): ?object;

    public function updateInteractionStatus(int $id, string $status): void;

    public function updateMessage(int $id, array $data): void;

    public function updateInteraction(int $id, array $data): void;

    public function findConversation(int $id): object;

    public function findOrCreateConversation(array $data): object;

    public function addMessage(array $data): object;

    public function findMessageByIdempotencyKey(string $key): ?object;

    public function findMessage(int $id): ?object;

    public function updateMessageStatus(int $id, string $status): void;

    public function messages(object $conversation): array;

    public function updateConversation(object $conversation, array $data): object;

    public function findWebhookEvent(string $channel, string $providerEventId): ?object;

    public function recordWebhookEvent(array $data): object;

    public function markWebhookProcessed(object $event): void;
}
