<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingFeeOption;
use App\Models\ShippingMethod;
use App\Models\ShippingPricingPlan;
use App\Models\ShippingPricingRule;
use App\Models\ShippingProvider;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Infrastructure\Persistence\DatabaseShippingRateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_weight_increment_and_lifecycle_fees_are_applied_only_for_the_matching_event(): void
    {
        [$order, $method, $plan] = $this->scenario(weight: 2.4);
        ShippingPricingRule::query()->create([
            'shipping_pricing_plan_id' => $plan->id,
            'rule_type' => 'base',
            'calculation_method' => 'base_plus_increment',
            'calculation_unit' => 'weight',
            'included_weight' => 1,
            'increment_unit' => 1,
            'increment_amount' => 15,
            'base_amount' => 70,
            'min_weight' => 0,
            'max_weight' => 10,
            'is_active' => true,
        ]);
        ShippingFeeOption::query()->create(['shipping_pricing_plan_id' => $plan->id, 'code' => 'delivery', 'name' => 'Delivery', 'fee_type' => 'fixed', 'amount' => 0, 'applies_to' => 'delivery', 'is_active' => true]);
        ShippingFeeOption::query()->create(['shipping_pricing_plan_id' => $plan->id, 'code' => 'return', 'name' => 'Return', 'fee_type' => 'fixed', 'amount' => 40, 'applies_to' => 'return', 'is_active' => true]);

        $calculator = app(DatabaseShippingRateCalculator::class);
        self::assertSame(100, $calculator->calculateBreakdown($order, $method, 'bosta')->total);
        self::assertSame(140, $calculator->calculateBreakdown($order, $method, 'bosta', 'return')->total);
    }

    public function test_quantity_based_per_unit_pricing_is_supported(): void
    {
        [$order, $method, $plan] = $this->scenario(weight: null, quantity: 3);
        $plan->update(['pricing_method' => 'quantity_based']);
        ShippingPricingRule::query()->create([
            'shipping_pricing_plan_id' => $plan->id,
            'rule_type' => 'base',
            'calculation_method' => 'per_unit',
            'calculation_unit' => 'quantity',
            'base_amount' => 25,
            'min_quantity' => 1,
            'max_quantity' => 10,
            'is_active' => true,
        ]);

        $result = app(DatabaseShippingRateCalculator::class)->calculateBreakdown($order, $method, 'bosta');
        self::assertSame(75, $result->total);
    }

    public function test_plan_currency_mismatch_is_rejected(): void
    {
        [$order, $method, $plan] = $this->scenario(weight: 1.0);
        $plan->update(['currency' => 'USD']);
        ShippingPricingRule::query()->create(['shipping_pricing_plan_id' => $plan->id, 'rule_type' => 'base', 'base_amount' => 50, 'min_weight' => 0, 'max_weight' => 5, 'is_active' => true]);

        $this->expectException(ShippingException::class);
        app(DatabaseShippingRateCalculator::class)->calculateBreakdown($order, $method, 'bosta');
    }

    /** @return array{0: CustomerOrder, 1: ShippingMethod, 2: ShippingPricingPlan} */
    private function scenario(?float $weight, int $quantity = 1): array
    {
        $provider = ShippingProvider::query()->create(['code' => 'bosta', 'name' => 'Bosta', 'is_active' => true]);
        $plan = ShippingPricingPlan::query()->create(['shipping_provider_id' => $provider->id, 'name' => 'Standard', 'pricing_method' => 'weight_based', 'currency' => 'EGP', 'is_active' => true]);
        $method = ShippingMethod::query()->create(['code' => 'standard', 'name' => 'Standard', 'carrier' => 'bosta', 'base_fee' => 50, 'currency' => 'EGP', 'is_active' => true]);
        $order = CustomerOrder::query()->create(['status' => 'processing', 'total_amount' => 1000, 'subtotal_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['zone_code' => 'cairo']]);
        $product = Product::query()->create(['name' => 'Test Product', 'slug' => uniqid('test-', true), 'type' => 'variable', 'status' => 'active']);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'sku' => uniqid('sku-', true), 'price' => 100, 'weight' => $weight, 'status' => 'active', 'combination_hash' => hash('sha256', uniqid('', true))]);
        CustomerOrderItem::query()->create(['order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => $variant->id, 'name' => $product->name, 'quantity' => $quantity, 'unit_price' => 100]);

        return [$order, $method, $plan];
    }
}
