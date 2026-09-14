<?php

namespace App\Modules\Customer\Domain\Contracts;

interface WishlistRepositoryInterface
{
    public function listForUser(int $userId): iterable;
    public function add(int $userId, int $productId): object;
    public function remove(int $userId, int $productId): void;
}
