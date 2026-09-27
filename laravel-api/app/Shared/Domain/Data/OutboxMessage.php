<?php

declare(strict_types=1);

namespace App\Shared\Domain\Data;

use DateTimeInterface;

final readonly class OutboxMessage
{
    public function __construct(
        public string $eventType,
        public string $aggregateType,
        public int $aggregateId,
        public array $payload = [],
        public ?DateTimeInterface $availableAt = null,
        public ?string $deduplicationKey = null,
    ) {}

    public function key(): string
    {
        return $this->deduplicationKey ?? implode(':', [$this->aggregateType, $this->eventType, $this->aggregateId]);
    }
}
