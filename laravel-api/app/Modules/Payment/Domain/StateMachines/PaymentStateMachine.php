<?php

namespace App\Modules\Payment\Domain\StateMachines;

use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;

final class PaymentStateMachine
{
    public static function assert(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        $allowed = [
            'pending' => ['processing', 'provider_created', 'confirmed', 'paid', 'failed'],
            'processing' => ['pending', 'provider_created', 'confirmed', 'paid', 'failed', 'ambiguous'],
            'provider_created' => ['confirmed', 'paid', 'failed'],
            'confirmed' => ['partially_refunded', 'refunded'],
            'paid' => ['confirmed', 'partially_refunded', 'refunded'],
            'partially_refunded' => ['partially_refunded', 'refunded'],
            'failed' => ['processing', 'confirmed'],
            'ambiguous' => ['processing', 'confirmed', 'failed'],
            'refunded' => [],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw InvalidPaymentTransitionException::from($from, $to);
        }
    }
}
