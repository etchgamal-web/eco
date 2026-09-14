<?php

namespace App\Modules\Tax\Domain\Contracts;

interface TaxCalculatorInterface
{
    /** @return array{amount:int, rate:string, rule_id:int|null} */
    public function calculate(int $subtotalAfterDiscount, string $country, ?string $state = null): array;
}
