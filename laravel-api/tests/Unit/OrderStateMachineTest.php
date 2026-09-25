<?php

namespace Tests\Unit;

use App\Modules\Order\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Order\Domain\StateMachines\OrderStateMachine;
use PHPUnit\Framework\TestCase;

final class OrderStateMachineTest extends TestCase
{
    public function test_only_the_next_workflow_step_is_allowed(): void
    {
        OrderStateMachine::assert('pending', 'reviewing');
        OrderStateMachine::assert('reviewing', 'confirmed');
        OrderStateMachine::assert('confirmed', 'processing');
        OrderStateMachine::assert('processing', 'shipped');
        OrderStateMachine::assert('shipped', 'delivered');
        OrderStateMachine::assert('delivered', 'refunded');
        self::assertTrue(true);
    }

    public function test_direct_confirmation_from_pending_is_rejected(): void
    {
        $this->expectException(InvalidOrderStatusTransitionException::class);
        OrderStateMachine::assert('pending', 'confirmed');
    }

    public function test_shipping_cannot_be_skipped(): void
    {
        $this->expectException(InvalidOrderStatusTransitionException::class);
        OrderStateMachine::assert('confirmed', 'shipped');
    }
}
