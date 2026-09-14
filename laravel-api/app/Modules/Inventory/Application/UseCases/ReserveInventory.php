<?php
namespace App\Modules\Inventory\Application\UseCases;
use App\Modules\Inventory\Domain\ValueObjects\StockReservationData;use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
final class ReserveInventory{public function __construct(private readonly InventoryRepositoryInterface $inventory){}public function execute(StockReservationData $data){return $this->inventory->reserve($data->productId,$data->variantId,$data->quantity);}}
