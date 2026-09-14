<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class DeleteShippingMethod
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(int $id): void { $this->methods->delete($this->methods->find($id)); }
}
