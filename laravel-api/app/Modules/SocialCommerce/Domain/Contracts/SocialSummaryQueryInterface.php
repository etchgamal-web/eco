<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface SocialSummaryQueryInterface
{
    /** @return array<string, int> */
    public function summary(): array;
}
