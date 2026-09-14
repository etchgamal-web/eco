<?php
namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use Closure;
use Illuminate\Support\Facades\DB;

final class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function run(Closure $operation): mixed
    {
        return DB::transaction($operation);
    }
}

