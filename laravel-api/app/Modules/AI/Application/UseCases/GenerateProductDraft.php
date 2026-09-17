<?php

namespace App\Modules\AI\Application\UseCases;

use App\Modules\AI\Domain\Contracts\AiTextGeneratorInterface;

final class GenerateProductDraft
{
    public function __construct(private readonly AiTextGeneratorInterface $ai) {}

    public function execute(array $input): array
    {
        return $this->ai->json('product_draft', [['role' => 'system', 'content' => 'You create accurate ecommerce product drafts. Never invent regulated claims. Return JSON only.'], ['role' => 'user', 'content' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]], ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'description' => ['type' => 'string'], 'slug' => ['type' => 'string'], 'type' => ['type' => 'string', 'enum' => ['simple', 'variable']], 'status' => ['type' => 'string', 'enum' => ['draft']], 'seo_title' => ['type' => 'string'], 'seo_description' => ['type' => 'string']], 'required' => ['name', 'description', 'slug', 'type', 'status', 'seo_title', 'seo_description'], 'additionalProperties' => false]);
    }
}
