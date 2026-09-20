<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Models\ShippingProvider;
use App\Modules\Shipping\Domain\Contracts\ShippingPricingCalculatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingRateCalculatorInterface;
use App\Modules\Shipping\Domain\ValueObjects\ShippingPricingBreakdown;
use Illuminate\Support\Carbon;

final class DatabaseShippingRateCalculator implements ShippingRateCalculatorInterface, ShippingPricingCalculatorInterface
{
    public function calculate(object $order, object $method): int
    {
        return $this->calculateBreakdown($order, $method, (string) ($method->carrier ?? ''))->total;
    }

    public function calculateBreakdown(object $order, object $method, string $providerCode): ShippingPricingBreakdown
    {
        $weight = 0.0;
        $quantity = 0;
        foreach ($order->items()->with('variant')->get() as $item) {
            $quantity += (int) $item->quantity;
            $weight += ((float) ($item->variant?->weight ?? 0)) * (int) $item->quantity;
        }
        $zoneCode = is_array($order->shipping_address ?? null) ? ($order->shipping_address['zone_code'] ?? null) : null;

        $providerCode = strtolower(trim($providerCode));
        $provider = ShippingProvider::query()->where('code', $providerCode)->where('is_active', true)->first();
        $plan = $provider?->pricingPlans()
            ->where('is_active', true)
            ->where(function ($query): void {
                $now = Carbon::now();
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query): void {
                $now = Carbon::now();
                $query->whereNull('effective_until')->orWhere('effective_until', '>=', $now);
            })
            ->latest('id')->first();

        if ($plan === null) {
            return new ShippingPricingBreakdown((int) $method->base_fee, [], $weight, $quantity, $zoneCode, null, null);
        }

        $rule = $plan->rules()->where('rule_type', 'base')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->first(fn ($candidate): bool => $this->matches($candidate, (string) $plan->pricing_method, $weight, $quantity, $zoneCode));
        $base = $rule === null ? 0 : (int) $rule->base_amount;
        if ($rule !== null && (int) $rule->additional_unit_amount > 0 && $rule->min_weight !== null) {
            $extraUnits = max(0, (int) ceil(max(0, $weight - (float) $rule->min_weight)));
            $base += $extraUnits * (int) $rule->additional_unit_amount;
        }

        $fees = [];
        foreach ($plan->feeOptions()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $fee) {
            $amount = $fee->fee_type === 'percentage'
                ? (int) round($base * ((int) $fee->amount / 100))
                : (int) $fee->amount;
            $fees[$fee->code] = ['name' => $fee->name, 'type' => $fee->fee_type, 'configured_value' => (int) $fee->amount, 'applied' => $amount, 'applies_to' => $fee->applies_to];
        }

        return new ShippingPricingBreakdown($base + array_sum(array_column($fees, 'applied')), $fees, $weight, $quantity, $zoneCode, $provider, $plan);
    }

    private function matches(object $rule, string $method, float $weight, int $quantity, ?string $zoneCode): bool
    {
        $weightMatches = ($rule->min_weight === null || $weight >= (float) $rule->min_weight)
            && ($rule->max_weight === null || $weight < (float) $rule->max_weight);
        $quantityMatches = ($rule->min_quantity === null || $quantity >= (int) $rule->min_quantity)
            && ($rule->max_quantity === null || $quantity <= (int) $rule->max_quantity);
        $zoneMatches = $rule->zone_code === null || $rule->zone_code === $zoneCode;
        return match ($method) {
            'quantity_based' => $quantityMatches && $zoneMatches,
            'zone_based' => $zoneMatches,
            'weight_zone' => $weightMatches && $zoneMatches,
            'custom' => $weightMatches && $quantityMatches && $zoneMatches,
            default => $weightMatches && $zoneMatches,
        };
    }
}
