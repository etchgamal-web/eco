<?php

namespace App\Modules\Tax\Infrastructure\Persistence;

use App\Models\TaxRule;
use App\Modules\Tax\Domain\Contracts\TaxCalculatorInterface;

final class DatabaseTaxCalculator implements TaxCalculatorInterface
{
    public function calculate(int $subtotalAfterDiscount, string $country, ?string $state = null): array
    {
        $rule = TaxRule::query()->where('is_active', true)->where(function ($query) use ($country): void {
            $query->whereNull('country')->orWhere('country', strtoupper($country));
        })->where(function ($query) use ($state): void {
            $query->whereNull('state')->orWhere('state', $state);
        })->orderByRaw('CASE WHEN state IS NOT NULL THEN 0 WHEN country IS NOT NULL THEN 1 ELSE 2 END')->first();
        if ($rule === null) {
            return ['amount' => 0, 'rate' => '0.0000', 'rule_id' => null];
        }
        return ['amount' => (int) round($subtotalAfterDiscount * (float) $rule->rate / 100), 'rate' => (string) $rule->rate, 'rule_id' => $rule->id];
    }
}
