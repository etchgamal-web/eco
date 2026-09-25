<?php

namespace App\Console\Commands;

use App\Modules\Monitoring\Application\UseCases\DetectDelayedOrders;
use Illuminate\Console\Command;

final class DetectOrderDelays extends Command
{
    protected $signature = 'orders:detect-delays';

    protected $description = 'Detect overdue orders and synchronize operational alerts';

    public function handle(DetectDelayedOrders $useCase): int
    {
        $result = $useCase->execute();
        $this->info("Created {$result['created']} alerts; resolved {$result['resolved']} alerts.");

        return self::SUCCESS;
    }
}
