<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class GetShippingMethod
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(int $id): object { return $this->methods->find($id); }
}
