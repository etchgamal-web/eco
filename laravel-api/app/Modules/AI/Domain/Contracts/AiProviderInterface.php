<?php

namespace App\Modules\AI\Domain\Contracts;

use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Domain\ValueObjects\AiResponse;

interface AiProviderInterface
{
    public function name(): string;

    public function generate(AiRequest $request): AiResponse;
}
