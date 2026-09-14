<?php

namespace App\Modules\Customer\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Customer\Domain\Contracts\CartRepositoryInterface;

final class ManageCustomerCart
{
    public function __construct(private readonly AuthenticationServiceInterface $auth, private readonly CartRepositoryInterface $cart) {}

    private function userId(): int
    {
        $user = $this->auth->user();
        if (!$user) throw new AuthenticationException('Unauthenticated.');
        return $user->id;
    }

    public function show(): object { return $this->cart->get($this->userId()); }
    public function add(int $productId, int $quantity): object { return $this->cart->addItem($this->userId(), $productId, null, $quantity); }
    public function update(int $productId, int $quantity): object { return $this->cart->updateItem($this->userId(), $productId, null, $quantity); }
    public function remove(int $productId): object { return $this->cart->removeItem($this->userId(), $productId, null); }
}
