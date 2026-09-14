<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;

final class ListPayments
{
    public function __construct(private readonly PaymentRepositoryInterface $payments) {}

    public function execute(int $orderId): iterable
    {
        return $this->payments->listForOrderAsAdmin($orderId);
    }
}
