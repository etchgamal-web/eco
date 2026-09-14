<?php

namespace App\Modules\Settings;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Infrastructure\Persistence\EloquentSettingsRepository;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        SettingsRepositoryInterface::class => EloquentSettingsRepository::class,
    ];
}
