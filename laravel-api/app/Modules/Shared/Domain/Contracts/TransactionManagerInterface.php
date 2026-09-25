<?php

namespace App\Modules\Shared\Domain\Contracts;

use Closure;

interface TransactionManagerInterface
{
    public function run(Closure $operation): mixed;
}
