<?php
namespace App\Modules\Inventory;
use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;use App\Modules\Inventory\Infrastructure\Persistence\EloquentInventoryRepository;use Illuminate\Support\ServiceProvider;
final class InventoryServiceProvider extends ServiceProvider{public array $bindings=[InventoryRepositoryInterface::class=>EloquentInventoryRepository::class];}
