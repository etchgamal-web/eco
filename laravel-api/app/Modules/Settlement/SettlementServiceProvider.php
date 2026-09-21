<?php
namespace App\Modules\Settlement;
use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;
use App\Modules\Settlement\Infrastructure\Persistence\EloquentSettlementRepository;
use Illuminate\Support\ServiceProvider;
final class SettlementServiceProvider extends ServiceProvider { public array $bindings=[SettlementRepositoryInterface::class=>EloquentSettlementRepository::class]; }
