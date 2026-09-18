<?php

namespace App\Modules\AI\Application\UseCases;

use App\Modules\AI\Domain\Contracts\AiTextGeneratorInterface;

final class SuggestSocialReply
{
    public function __construct(private readonly AiTextGeneratorInterface $ai) {}

    public function execute(array $input): array
    {
        return $this->ai->json('social_reply_suggestion', [['role' => 'system', 'content' => 'You draft concise, polite ecommerce social replies in the requested language. Do not claim actions not provided. Return JSON only.'], ['role' => 'user', 'content' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]], ['type' => 'object', 'properties' => ['reply' => ['type' => 'string'], 'language' => ['type' => 'string'], 'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1], 'needs_human_review' => ['type' => 'boolean']], 'required' => ['reply', 'language', 'confidence', 'needs_human_review'], 'additionalProperties' => false]);
    }
}
