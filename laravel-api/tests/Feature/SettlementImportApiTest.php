<?php
namespace Tests\Feature;
use App\Models\CustomerOrder;
use App\Models\CustomerNotification;
use App\Models\OperationalAlert;
use App\Models\OrderReturn;
use App\Models\OrderMonitoringSetting;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ShippingProvider;
use App\Models\ShippingSettlement;
use App\Models\ShippingSettlementItem;
use App\Modules\Settlement\Domain\Exceptions\SettlementImportException;
use App\Modules\Settlement\Infrastructure\Persistence\EloquentSettlementRepository;
use App\Modules\Monitoring\Infrastructure\Persistence\EloquentMonitoringRepository;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
final class SettlementImportApiTest extends TestCase
{
    use RefreshDatabase;
    private array $files = [];
    protected function setUp(): void { parent::setUp(); $this->seed(RbacSeeder::class); ShippingProvider::query()->create(['code' => 'test-provider', 'name' => 'Test Provider', 'is_active' => true]); }
    protected function tearDown(): void { foreach ($this->files as $file) @unlink($file); parent::tearDown(); }
    public function test_import_counts_invalid_missing_duplicate_and_mismatched_rows(): void
    {
        $shipment = $this->shipment('TRK-1', 100); $this->shipment('TRK-2', 200);
        $file = $this->csv("tracking_number,actual_total\nTRK-1,120\nTRK-1,120\nTRK-UNKNOWN,100\n,not-a-number\nTRK-2,200\n");
        $settlement = app(EloquentSettlementRepository::class)->import($file, 'test-provider', null, null);
        self::assertSame(['matched' => 1, 'mismatched' => 1, 'missing' => 1, 'duplicates' => 1, 'invalid' => 1], array_intersect_key($settlement->metadata, array_flip(['matched', 'mismatched', 'missing', 'duplicates', 'invalid'])));
        self::assertSame(2, $settlement->items()->count());
        self::assertNull($settlement->period_from);
        self::assertNull($settlement->period_to);
        self::assertSame(20, $settlement->difference_total);
        self::assertDatabaseHas('shipping_settlement_items', ['shipment_id' => $shipment->id, 'status' => 'mismatched', 'difference' => 20]);
    }
    public function test_import_rejects_file_when_manager_configured_row_limit_is_exceeded(): void
    {
        $this->shipment('TRK-LIMIT', 100); $repo = app(EloquentSettlementRepository::class); $repo->saveSetting('settlement_import.max_rows', 2);
        $file = $this->csv("tracking_number,actual_total\nTRK-LIMIT,100\nTRK-LIMIT,100\nTRK-LIMIT,100\n");
        $this->expectException(SettlementImportException::class); $this->expectExceptionMessage('maximum of 2 rows'); $repo->import($file, 'test-provider', null, null); self::assertDatabaseCount('shipping_settlements', 0);
    }
    public function test_import_reconciles_order_collection_shipping_and_return_amounts_separately(): void
    {
        $shipment = $this->shipment('TRK-FIN', 30, 'ORD-100'); $file = $this->csv("order_number,tracking_number,order_amount,collected_amount,shipping_cost,return_fee\nORD-100,TRK-FIN,90,95,35,0\n");
        $settlement = app(EloquentSettlementRepository::class)->import($file, 'test-provider', null, null); $item = $settlement->items()->first();
        self::assertSame('ORD-100', $item->order_number); self::assertSame(100, $item->expected_order_amount); self::assertSame(90, $item->actual_order_amount); self::assertSame(100, $item->expected_collection); self::assertSame(95, $item->actual_collection); self::assertSame(30, $item->expected_shipping_cost); self::assertSame(35, $item->actual_shipping_cost); self::assertSame('mismatched', $item->status); self::assertSame('completed', $settlement->status); self::assertSame($shipment->id, $item->shipment_id);
    }
    public function test_auto_finalize_never_finalizes_when_reconciliation_has_mismatches(): void
    {
        $this->shipment('TRK-AUTO', 100, 'ORD-AUTO'); $repo = app(EloquentSettlementRepository::class); $repo->saveSetting('settlement_import.auto_finalize', true); $file = $this->csv("order_number,tracking_number,order_amount,collected_amount,shipping_cost\nORD-AUTO,TRK-AUTO,1,1,1\n");
        $settlement = $repo->import($file, 'test-provider', null, null); self::assertSame('completed', $settlement->status);
    }
    public function test_return_refund_and_return_shipping_fee_are_reconciled_as_separate_amounts(): void
    {
        $shipment = $this->shipment('TRK-RETURN-FIN', 30, 'ORD-RETURN-FIN'); OrderReturn::query()->create(['order_id' => $shipment->order_id, 'shipment_id' => $shipment->id, 'user_id' => $shipment->user_id, 'status' => 'approved', 'reason' => 'customer_request', 'refund_amount' => 500, 'return_shipping_fee' => 40]);
        $file = $this->csv("order_number,tracking_number,order_amount,collected_amount,shipping_cost,customer_refund,return_fee\nORD-RETURN-FIN,TRK-RETURN-FIN,100,100,30,500,40\n"); $settlement = app(EloquentSettlementRepository::class)->import($file, 'test-provider', null, null); $item = $settlement->items()->first();
        self::assertSame(500, $item->expected_customer_refund); self::assertSame(500, $item->actual_customer_refund); self::assertSame(40, $item->expected_return_fee); self::assertSame(40, $item->actual_return_fee); self::assertSame(70, $item->expected_total); self::assertSame('matched', $item->status); self::assertSame(500, $settlement->expected_customer_refund); self::assertSame(40, $settlement->expected_return_fee);
    }
    public function test_delivered_shipment_without_settlement_creates_settlement_missing_alert(): void
    {
        $admin = \App\Models\User::factory()->create(); $admin->roles()->attach(Role::query()->where('slug', 'admin')->value('id')); OrderMonitoringSetting::query()->updateOrCreate(['rule_type' => 'settlement_missing'], ['days' => 1, 'is_enabled' => true]); $shipment = $this->shipment('TRK-MISSING', 30, 'ORD-MISSING'); $old = Carbon::now()->subDays(5); DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'delivered', 'created_at' => $old, 'updated_at' => $old]); DB::table('customer_orders')->where('id', $shipment->order_id)->update(['status' => 'delivered', 'updated_at' => $old]);
        app(EloquentMonitoringRepository::class)->detect(); self::assertDatabaseHas('operational_alerts', ['order_id' => $shipment->order_id, 'type' => 'settlement_missing', 'status' => 'open']); self::assertDatabaseMissing('operational_alerts', ['order_id' => $shipment->order_id, 'type' => 'delivery_overdue']); $notification = CustomerNotification::query()->latest('id')->first(); self::assertNotNull($notification); self::assertSame('Settlement is missing', $notification->title); self::assertStringContainsString('ORD-MISSING', $notification->body); self::assertStringContainsString('completed settlement file', $notification->body);
    }
    public function test_processing_settlement_item_does_not_clear_settlement_missing_alert(): void
    {
        OrderMonitoringSetting::query()->updateOrCreate(['rule_type' => 'settlement_missing'], ['days' => 1, 'is_enabled' => true]); $shipment = $this->shipment('TRK-PENDING', 30, 'ORD-PENDING'); $old = Carbon::now()->subDays(5); DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'delivered', 'created_at' => $old, 'updated_at' => $old]); $settlement = ShippingSettlement::query()->create(['shipping_provider_id' => ShippingProvider::query()->first()->id, 'reference' => 'pending', 'period_from' => null, 'period_to' => null, 'status' => 'processing', 'currency' => 'EGP']); ShippingSettlementItem::query()->create(['shipping_settlement_id' => $settlement->id, 'shipment_id' => $shipment->id, 'status' => 'matched']);
        app(EloquentMonitoringRepository::class)->detect(); self::assertDatabaseHas('operational_alerts', ['order_id' => $shipment->order_id, 'type' => 'settlement_missing', 'status' => 'open']); $settlement->update(['status' => 'completed']); app(EloquentMonitoringRepository::class)->detect(); self::assertDatabaseHas('operational_alerts', ['order_id' => $shipment->order_id, 'type' => 'settlement_missing', 'status' => 'resolved']);
    }
    public function test_approved_return_without_settlement_creates_return_settlement_missing_alert(): void
    {
        OrderMonitoringSetting::query()->updateOrCreate(['rule_type' => 'return_settlement_missing'], ['days' => 1, 'is_enabled' => true]); $shipment = $this->shipment('TRK-RETURN', 30, 'ORD-RETURN'); $old = Carbon::now()->subDays(5); DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'delivered', 'created_at' => $old, 'updated_at' => $old]); $return = OrderReturn::query()->create(['order_id' => $shipment->order_id, 'user_id' => $shipment->user_id, 'status' => 'approved', 'reason' => 'damaged', 'refund_amount' => 30]); DB::table('order_returns')->where('id', $return->id)->update(['created_at' => $old, 'updated_at' => $old]);
        app(EloquentMonitoringRepository::class)->detect(); self::assertDatabaseHas('operational_alerts', ['order_id' => $shipment->order_id, 'type' => 'return_settlement_missing', 'status' => 'open']);
    }
    public function test_import_handles_large_csv_as_a_stream_without_loading_all_rows(): void
    {
        $this->shipment('TRK-LARGE', 100); $lines = ['tracking_number,actual_total']; for ($i = 0; $i < 1000; $i++) $lines[] = 'TRK-LARGE,100';
        $file = $this->csv(implode("\n", $lines)."\n"); $settlement = app(EloquentSettlementRepository::class)->import($file, 'test-provider', null, null);
        self::assertSame(1, $settlement->shipments_count); self::assertSame(1000, $settlement->total_rows); self::assertSame(999, $settlement->metadata['duplicates']); self::assertSame(1, $settlement->metadata['matched']);
    }
    private function shipment(string $tracking, int $fee, ?string $orderNumber = null): Shipment { $customer = \App\Models\User::factory()->create(); $order = CustomerOrder::query()->create(['order_number' => $orderNumber, 'user_id' => $customer->id, 'status' => 'processing', 'total_amount' => 100, 'currency' => 'EGP']); $method = ShippingMethod::query()->create(['code' => 'standard-'.uniqid(), 'name' => 'Standard', 'base_fee' => $fee, 'currency' => 'EGP', 'is_active' => true]); return Shipment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'shipping_method_id' => $method->id, 'method_code' => $method->code, 'provider_code' => 'test-provider', 'tracking_number' => $tracking, 'fee' => $fee, 'currency' => 'EGP', 'status' => 'in_transit', 'address_snapshot' => [], 'idempotency_key' => uniqid('settlement-', true)]); }
    private function csv(string $content): string { $file = tempnam(sys_get_temp_dir(), 'settlement-'); file_put_contents($file, $content); $this->files[] = $file; return $file; }
}
