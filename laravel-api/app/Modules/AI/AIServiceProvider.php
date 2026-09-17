<?php

namespace App\Modules\AI;

use App\Modules\AI\Domain\Contracts\AiGatewayInterface;
use App\Modules\AI\Domain\Contracts\AiTextGeneratorInterface;
use App\Modules\AI\Infrastructure\AiGateway;
use Illuminate\Support\ServiceProvider;

final class AIServiceProvider extends ServiceProvider
{
    public array $bindings = [AiTextGeneratorInterface::class => AiGateway::class, AiGatewayInterface::class => AiGateway::class];
}
