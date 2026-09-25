<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Modules\Shipping\Domain\Contracts\ShippingPricingCalculatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingRateCalculatorInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Domain\ValueObjects\ShippingPricingBreakdown;
use App\Modules\Shipping\Infrastructure\Models\ShippingProvider;
use Illuminate\Support\Carbon;

final class DatabaseShippingRateCalculator implements ShippingPricingCalculatorInterface, ShippingRateCalculatorInterface
{
    public function calculate(object $order, object $method): int
    {
        return $this->calculateBreakdown($order, $method, (string) ($method->carrier ?? ''))->total;
    }

    public function calculateBreakdown(object $order, object $method, string $providerCode, string $event = 'delivery', array $context = []): ShippingPricingBreakdown
    {
        $weight = 0.0;
        $quantity = 0;
        $missingWeight = false;
        foreach ($order->items()->with('variant')->get() as $item) {
            $quantity += (int) $item->quantity;
            if ($item->variant?->weight === null) {
                $missingWeight = true;

                continue;
            }
            $weight += (float) $item->variant->weight * (int) $item->quantity;
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
            return new ShippingPricingBreakdown((int) $method->base_fee, [], $weight, $quantity, $zoneCode, $provider, null, 'legacy/base_fee');
        }
        if (strtoupper((string) $plan->currency) !== strtoupper((string) $order->currency)) {
            throw new ShippingException('Shipping pricing plan currency does not match the order.');
        }
        if ($missingWeight && in_array($plan->pricing_method, ['weight_based', 'weight_zone', 'custom'], true)) {
            throw new ShippingException('A product weight is required for this shipping pricing plan.');
        }

        $rule = $plan->rules()->where('rule_type', 'base')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->first(fn ($candidate): bool => $this->matches($candidate, (string) $plan->pricing_method, $weight, $quantity, $zoneCode));
        $base = $rule === null ? 0 : $this->ruleAmount($rule, $weight, $quantity);

        $fees = [];
        foreach ($plan->feeOptions()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $fee) {
            if (! $this->feeApplies($fee, $event, $context)) {
                continue;
            }
            $basis = $this->basisAmount($fee->calculation_basis, $base, $order, $context);
            $amount = $fee->fee_type === 'percentage'
                ? (int) round($basis * ((int) $fee->amount / 100))
                : (int) $fee->amount;
            $fees[$fee->code] = ['name' => $fee->name, 'type' => $fee->fee_type, 'basis' => $fee->calculation_basis, 'basis_amount' => $basis, 'configured_value' => (int) $fee->amount, 'applied' => $amount, 'applies_to' => $fee->applies_to, 'event' => $event];
        }

        return new ShippingPricingBreakdown($base + array_sum(array_column($fees, 'applied')), $fees, $weight, $quantity, $zoneCode, $provider, $plan, (string) $plan->pricing_method);
    }

    private function ruleAmount(object $rule, float $weight, int $quantity): int
    {
        $method = (string) ($rule->calculation_method ?? 'flat');
        $unit = (string) ($rule->calculation_unit ?? '');
        $units = $unit === 'quantity' ? $quantity : $weight;
        if ($method === 'per_unit') {
            return (int) round((int) $rule->base_amount * $units);
        }
        if ($method === 'base_plus_increment') {
            $included = $rule->included_weight !== null ? (float) $rule->included_weight : (float) ($rule->min_weight ?? 0);
            $incrementUnit = $rule->increment_unit !== null ? (float) $rule->increment_unit : 1.0;
            $incrementAmount = (int) ($rule->increment_amount ?: $rule->additional_unit_amount);
            $extraUnits = $incrementUnit > 0 ? (int) ceil(max(0, $units - $included) / $incrementUnit) : 0;

            return (int) $rule->base_amount + ($extraUnits * $incrementAmount);
        }

        return (int) $rule->base_amount;
    }

    private function feeApplies(object $fee, string $event, array $context): bool
    {
        $appliesTo = strtolower((string) $fee->applies_to);
        $event = strtolower($event);
        $event = $event === 'delivered' ? 'delivery' : $event;
        if (! in_array($appliesTo, ['all', 'any', $event], true)) {
            return false;
        }
        foreach ((array) ($fee->trigger_conditions ?? []) as $key => $expected) {
            if ((bool) ($context[$key] ?? false) !== (bool) $expected && (string) ($context[$key] ?? '') !== (string) $expected) {
                return false;
            }
        }

        return true;
    }

    private function basisAmount(string $basis, int $base, object $order, array $context): int
    {
        return match ($basis) {
            'order_subtotal' => (int) ($order->subtotal_amount ?? $order->total_amount ?? 0),
            'cod_amount' => (int) ($context['cod_amount'] ?? 0),
            'declared_value' => (int) ($context['declared_value'] ?? $order->total_amount ?? 0),
            default => $base,
        };
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
