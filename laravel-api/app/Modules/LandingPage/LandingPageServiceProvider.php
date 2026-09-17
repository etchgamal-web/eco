<?php

namespace App\Modules\LandingPage;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;
use App\Modules\LandingPage\Infrastructure\Persistence\EloquentLandingPageRepository;
use Illuminate\Support\ServiceProvider;

class LandingPageServiceProvider extends ServiceProvider
{
    public array $bindings = [LandingPageRepositoryInterface::class => EloquentLandingPageRepository::class];
}
