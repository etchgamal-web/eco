<?php

namespace App\Modules\AI\Domain\Contracts;

interface AiTextGeneratorInterface
{
    public function json(string $kind, array $messages, array $schema): array;
}
