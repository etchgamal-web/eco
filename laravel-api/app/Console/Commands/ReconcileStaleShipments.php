<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Modules\Shipping\Application\UseCases\ReconcileShipment;
use Illuminate\Console\Command;

if (! class_exists(__NAMESPACE__ . '\\ReconcileStaleShipments', false)) {
final class ReconcileStaleShipments extends Command
{
    protected $signature = 'shipments:reconcile {--minutes=10 : Minimum age of an active shipment}';
    protected $description = 'Reconcile active shipments with their carrier tracking API';

    public function handle(): int
    {
        $count = 0;
        Shipment::query()->whereIn('status', ['pending', 'processing', 'provider_created', 'picked_up', 'in_transit', 'out_for_delivery'])
            ->where('updated_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->orderBy('id')->limit(100)->pluck('id')->each(function (int $shipmentId) use (&$count): void {
                try {
                    app(ReconcileShipment::class)->execute($shipmentId);
                    $count++;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        $this->info("Reconciled {$count} shipment(s).");
        return self::SUCCESS;
    }
}
}
