<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class ListAllShippingMethods
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(): iterable { return $this->methods->listAll(); }
}
