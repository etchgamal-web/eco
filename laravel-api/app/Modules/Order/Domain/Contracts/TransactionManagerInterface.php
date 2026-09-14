<?php
namespace App\Modules\Order\Domain\Contracts;

use Closure;

interface TransactionManagerInterface
{
    public function run(Closure $operation): mixed;
}

