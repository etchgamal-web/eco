<?php
namespace App\Modules\Inventory\Application\UseCases;
use App\Modules\Inventory\Domain\ValueObjects\StockAdjustmentData;use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
final class AdjustInventory{public function __construct(private readonly InventoryRepositoryInterface $inventory){}public function execute(StockAdjustmentData $data,object $actor){return $this->inventory->adjust($data,$actor->id);}}
