<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface SocialMessagingProviderInterface
{
    public function channel(): string;

    public function verifyWebhook(array $headers, string $rawBody, ?string $secret = null): bool;

    public function normalizeWebhook(array $payload): array;

    public function sendMessage(object $connection, string $recipient, string $text): array;

    public function replyToComment(object $connection, string $commentId, string $text): array;
}
