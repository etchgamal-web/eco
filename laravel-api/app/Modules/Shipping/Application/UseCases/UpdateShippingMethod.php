<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;

final class UpdateShippingMethod
{
    public function __construct(private readonly ShippingMethodRepositoryInterface $methods) {}
    public function execute(int $id, array $data): object { return $this->methods->update($this->methods->find($id), $data); }
}
