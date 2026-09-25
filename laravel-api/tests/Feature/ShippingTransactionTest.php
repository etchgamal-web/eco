<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Shipping\Application\UseCases\CreateShipment;
use App\Modules\Shipping\Domain\Contracts\ShipmentPricingSnapshotRepositoryInterface;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
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
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'shipping_cost' => 0]);
    }
}
