<?php
namespace App\Modules\Inventory\Domain\Contracts;
use App\Modules\Inventory\Domain\ValueObjects\StockAdjustmentData;
interface InventoryRepositoryInterface{public function list():iterable;public function find(int $id):object;public function adjust(StockAdjustmentData $data,?int $actorId):object;public function reserve(int $productId,?int $variantId,int $quantity):object;public function release(int $productId,?int $variantId,int $quantity):object;public function commit(int $productId,?int $variantId,int $quantity,?int $actorId=null):object;}
