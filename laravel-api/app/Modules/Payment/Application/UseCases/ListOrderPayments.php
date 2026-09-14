<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;

final class ListOrderPayments
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
    ) {}

    public function execute(int $orderId): iterable
    {
        $user = $this->authentication->user();
        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }
        $this->orders->findForUser($user->id, $orderId);

        return $this->payments->listForOrder($user->id, $orderId);
    }
}
