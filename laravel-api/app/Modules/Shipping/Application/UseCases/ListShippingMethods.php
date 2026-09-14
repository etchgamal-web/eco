<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class ListShippingMethods
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(): iterable { return $this->methods->listActive(); }
}
