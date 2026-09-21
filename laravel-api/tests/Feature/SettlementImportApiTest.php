<?php
namespace Tests\Feature;
use App\Models\CustomerOrder;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ShippingProvider;
use App\Modules\Settlement\Domain\Exceptions\SettlementImportException;
use App\Modules\Settlement\Infrastructure\Persistence\EloquentSettlementRepository;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        self::assertSame(20, $settlement->difference_total);
        self::assertDatabaseHas('shipping_settlement_items', ['shipment_id' => $shipment->id, 'status' => 'mismatched', 'difference' => 20]);
    }
    public function test_import_rejects_file_when_manager_configured_row_limit_is_exceeded(): void
    {
        $this->shipment('TRK-LIMIT', 100); $repo = app(EloquentSettlementRepository::class); $repo->saveSetting('settlement_import.max_rows', 2);
        $file = $this->csv("tracking_number,actual_total\nTRK-LIMIT,100\nTRK-LIMIT,100\nTRK-LIMIT,100\n");
        $this->expectException(SettlementImportException::class); $this->expectExceptionMessage('maximum of 2 rows'); $repo->import($file, 'test-provider', null, null);
    }
    public function test_import_handles_large_csv_as_a_stream_without_loading_all_rows(): void
    {
        $this->shipment('TRK-LARGE', 100); $lines = ['tracking_number,actual_total']; for ($i = 0; $i < 1000; $i++) $lines[] = 'TRK-LARGE,100';
        $file = $this->csv(implode("\n", $lines)."\n"); $settlement = app(EloquentSettlementRepository::class)->import($file, 'test-provider', null, null);
        self::assertSame(1000, $settlement->shipments_count); self::assertSame(999, $settlement->metadata['duplicates']); self::assertSame(1, $settlement->metadata['matched']);
    }
    private function shipment(string $tracking, int $fee): Shipment { $customer = \App\Models\User::factory()->create(); $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'processing', 'total_amount' => $fee, 'currency' => 'EGP']); $method = ShippingMethod::query()->create(['code' => 'standard-'.uniqid(), 'name' => 'Standard', 'base_fee' => $fee, 'currency' => 'EGP', 'is_active' => true]); return Shipment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'shipping_method_id' => $method->id, 'method_code' => $method->code, 'provider_code' => 'test-provider', 'tracking_number' => $tracking, 'fee' => $fee, 'currency' => 'EGP', 'status' => 'in_transit', 'address_snapshot' => [], 'idempotency_key' => uniqid('settlement-', true)]); }
    private function csv(string $content): string { $file = tempnam(sys_get_temp_dir(), 'settlement-'); file_put_contents($file, $content); $this->files[] = $file; return $file; }
}
