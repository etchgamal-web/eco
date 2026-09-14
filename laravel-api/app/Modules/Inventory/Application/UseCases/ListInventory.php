<?php
namespace App\Modules\Inventory\Application\UseCases;
use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
final class ListInventory{public function __construct(private readonly InventoryRepositoryInterface $inventory){}public function execute():iterable{return $this->inventory->list();}}
