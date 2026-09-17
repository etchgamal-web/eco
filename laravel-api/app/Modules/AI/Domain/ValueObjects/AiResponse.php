<?php

namespace App\Modules\AI\Domain\ValueObjects;

final readonly class AiResponse
{
    public function __construct(public array $data, public string $provider, public string $model, public int $promptTokens = 0, public int $completionTokens = 0) {}

    public function toArray(): array
    {
        return $this->data;
    }
}
