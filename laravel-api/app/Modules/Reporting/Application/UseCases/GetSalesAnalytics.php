<?php

namespace App\Modules\Reporting\Application\UseCases;

use App\Modules\Reporting\Domain\Contracts\SalesAnalyticsReaderInterface;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class GetSalesAnalytics
{
    public function __construct(private readonly SalesAnalyticsReaderInterface $reader) {}

    /** @return array<string, mixed> */
    public function execute(string $from, string $to, bool $compare): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
        if (! $start || ! $end || $start > $end) throw new InvalidArgumentException('Invalid reporting date range.');

        $current = $this->reader->read($start->format('Y-m-d'), $end->format('Y-m-d'));
        $result = ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'current' => $current, 'previous' => null];
        if ($compare) {
            $days = $start->diff($end)->days + 1;
            $previousEnd = $start->modify('-1 day');
            $previousStart = $previousEnd->modify('-'.($days - 1).' days');
            $result['previous'] = ['from' => $previousStart->format('Y-m-d'), 'to' => $previousEnd->format('Y-m-d'), 'data' => $this->reader->read($previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'))];
        }
        return $result;
    }
}
