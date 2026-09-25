<?php

namespace App\Modules\Order\Domain\StateMachines;

use App\Modules\Order\Domain\Exceptions\InvalidOrderStatusTransitionException;

final class OrderStateMachine
{
    public static function assert(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = [
            'pending' => ['reviewing', 'cancelled'],
            'reviewing' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw InvalidOrderStatusTransitionException::from($from, $to);
        }
    }
}
