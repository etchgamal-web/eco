<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Shipping\Application\Outbox\ShippingOutboxHandler;
use App\Modules\Shipping\Application\UseCases\CreateShipment;
use App\Modules\Shipping\Domain\Contracts\ShipmentPricingSnapshotRepositoryInterface;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class ShippingTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_failure_rolls_back_shipment_and_order_shipping_cost(): void
    {
        $user = User::factory()->create();
        $method = ShippingMethod::query()->create([
            'code' => 'standard',
            'name' => 'Standard',
            'carrier' => 'bosta',
            'base_fee' => 150,
            'currency' => 'EGP',
            'is_active' => true,
        ]);
        $order = CustomerOrder::query()->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'total_amount' => 1000,
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'shipping_cost' => 0,
            'shipping_subsidy' => 0,
            'currency' => 'EGP',
            'shipping_address' => ['city' => 'Cairo'],
        ]);

        $this->mock(ShipmentPricingSnapshotRepositoryInterface::class, function ($mock): void {
            $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('snapshot failed'));
        });

        try {
            app(CreateShipment::class)->execute($order->id, new CreateShipmentData($method->id, 'atomic-shipment', 'bosta'));
            self::fail('Expected snapshot creation to fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('snapshot failed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('shipments', ['idempotency_key' => 'atomic-shipment']);
        self::assertDatabaseHas('customer_orders', ['id' => $order->id, 'shipping_cost' => 0]);
    }

    public function test_replaying_created_shipment_repairs_processing_order_before_outbox_completion(): void
    {
        $user = User::factory()->create();
        $method = ShippingMethod::query()->create([
            'code' => 'bosta-replay', 'name' => 'Bosta Replay', 'carrier' => 'bosta', 'base_fee' => 150,
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $order = CustomerOrder::query()->create([
            'user_id' => $user->id, 'status' => 'processing', 'total_amount' => 1000,
            'subtotal_amount' => 1000, 'discount_amount' => 0, 'tax_amount' => 0,
            'shipping_amount' => 0, 'shipping_cost' => 0, 'shipping_subsidy' => 0,
            'currency' => 'EGP', 'shipping_address' => ['city' => 'Cairo'],
        ]);
        $shipment = Shipment::query()->create([
            'order_id' => $order->id, 'user_id' => $user->id, 'shipping_method_id' => $method->id,
            'method_code' => $method->code, 'provider_code' => 'bosta', 'fee' => 150, 'currency' => 'EGP',
            'status' => 'provider_created', 'creation_status' => 'created', 'tracking_number' => 'BOSTA-REPLAY',
            'address_snapshot' => ['city' => 'Cairo'], 'idempotency_key' => 'replay-shipment-'.$order->id,
            'metadata' => ['provider_reference' => 'BOSTA-REPLAY'],
        ]);
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'shipment', 'aggregate_id' => $shipment->id,
            'event_type' => 'shipment.create.requested', 'deduplication_key' => 'shipment:replay:'.$shipment->id,
            'status' => 'processing', 'claim_token' => 'replay-claim', 'payload' => [],
        ]);

        app(ShippingOutboxHandler::class)->handle($event);

        self::assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'shipped']);
        self::assertDatabaseHas('outbox_events', ['id' => $event->id, 'status' => 'dispatched']);
    }
}
