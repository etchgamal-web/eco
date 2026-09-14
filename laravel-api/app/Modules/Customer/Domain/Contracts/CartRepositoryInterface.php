<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CartRepositoryInterface
{
    public function get(int $userId): object;
    public function addItem(int $userId, int $productId, ?int $variantId, int $quantity): object;
    public function updateItem(int $userId, int $productId, ?int $variantId, int $quantity): object;
    public function removeItem(int $userId, int $productId, ?int $variantId): object;
    public function clear(int $userId): object;
}
