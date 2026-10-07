<?php

namespace App\Modules\Content;

use App\Modules\Content\Domain\Contracts\ContentRepositoryInterface;
use App\Modules\Content\Infrastructure\Persistence\EloquentContentRepository;
use Illuminate\Support\ServiceProvider;

final class ContentServiceProvider extends ServiceProvider
{
    public array $bindings = [ContentRepositoryInterface::class => EloquentContentRepository::class];
}
