<?php

namespace App\Modules\Shared\Application\Outbox;

interface OutboxEventHandlerInterface
{
    public function supports(string $eventType): bool;

    public function handle(object $event): void;

    public function failed(object $event, \Throwable $exception): void;
}
