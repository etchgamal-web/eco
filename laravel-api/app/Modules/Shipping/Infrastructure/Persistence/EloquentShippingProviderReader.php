<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Modules\Shipping\Domain\Contracts\ShippingProviderReaderInterface;
use App\Modules\Shipping\Infrastructure\Models\ShippingProvider;

final class EloquentShippingProviderReader implements ShippingProviderReaderInterface
{
    public function active(): iterable
    {
        return ShippingProvider::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'metadata']);
    }
}
