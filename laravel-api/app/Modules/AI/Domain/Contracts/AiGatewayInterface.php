<?php

namespace App\Modules\AI\Domain\Contracts;

use App\Modules\AI\Domain\ValueObjects\AiRequest;
use App\Modules\AI\Domain\ValueObjects\AiResponse;

interface AiGatewayInterface
{
    public function generate(AiRequest $request): AiResponse;
}
