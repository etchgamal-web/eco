<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;

final class ListCustomerOrders
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function execute(): iterable
    {
        $user = $this->authentication->user();
        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->orders->listForUser($user->id);
    }
}
