<?php

namespace App\Modules\SocialCommerce\Infrastructure\Providers;

final class FacebookMessagingProvider extends MetaChannelMessagingProvider
{
    public function channel(): string
    {
        return 'facebook';
    }

    protected function messagePayload(string $recipient, string $text): array
    {
        return ['recipient' => ['id' => $recipient], 'message' => ['text' => $text]];
    }

    protected function commentReplyUrl(string $commentId): string
    {
        $template = (string) config('services.social.facebook.comment_reply_url', '');

        return $template !== '' ? str_replace('{comment_id}', rawurlencode($commentId), $template) : '';
    }
}
