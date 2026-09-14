<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingMethodRepositoryInterface
{
    public function listActive(): iterable;
    public function listAll(): iterable;
    public function find(int $id): object;
    public function create(array $attributes): object;
    public function update(object $method, array $attributes): object;
    public function delete(object $method): void;
}
