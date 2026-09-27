<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface OutboxEventHandlerInterface
{
    public function supports(string $eventType): bool;
    public function handle(object $event): void;
    public function failed(object $event, \Throwable $exception): void;
}
