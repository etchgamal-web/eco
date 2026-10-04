<?php

namespace App\Modules\Reporting;

use App\Modules\Reporting\Application\UseCases\GetSalesAnalytics;
use App\Modules\Reporting\Domain\Contracts\SalesAnalyticsReaderInterface;
use App\Modules\Reporting\Infrastructure\Persistence\EloquentSalesAnalyticsReader;
use Illuminate\Support\ServiceProvider;

final class ReportingServiceProvider extends ServiceProvider
{
    public array $bindings = [SalesAnalyticsReaderInterface::class => EloquentSalesAnalyticsReader::class];
}
