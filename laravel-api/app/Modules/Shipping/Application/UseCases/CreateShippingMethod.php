<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class CreateShippingMethod
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(array $data): object { return $this->methods->create($data); }
}
