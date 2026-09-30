<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingProviderReaderInterface;

final class ListShippingProviders
{
    public function __construct(private readonly ShippingProviderReaderInterface $providers) {}

    public function execute(): iterable
    {
        return $this->providers->active();
    }
}
