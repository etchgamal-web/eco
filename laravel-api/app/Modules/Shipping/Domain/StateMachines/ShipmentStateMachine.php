<?php

namespace App\Modules\Shipping\Domain\StateMachines;

use App\Modules\Shipping\Domain\Exceptions\InvalidShipmentTransitionException;

final class ShipmentStateMachine
{
    public static function assert(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        $allowed = [
            'pending' => ['processing', 'provider_created', 'picked_up', 'cancelled'],
            'processing' => ['provider_created', 'failed', 'cancelled'],
            'provider_created' => ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'],
            'picked_up' => ['in_transit', 'out_for_delivery', 'cancelled'],
            'in_transit' => ['out_for_delivery', 'delivered', 'cancelled'],
            'out_for_delivery' => ['delivered', 'cancelled'],
            'delivered' => [],
            'failed' => ['processing', 'provider_created'],
            'cancelled' => [],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw InvalidShipmentTransitionException::from($from, $to);
        }
    }
}
