<?php

namespace App\Modules\AI\Domain\ValueObjects;

final readonly class AiRequest
{
    public function __construct(public string $kind, public array $messages, public array $schema, public array $metadata = []) {}
}
