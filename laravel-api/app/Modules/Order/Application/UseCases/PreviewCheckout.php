<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Application\Services\CheckoutOrderService;
use App\Modules\Order\Domain\ValueObjects\CheckoutData;

final class PreviewCheckout
{
    public function __construct(private readonly CheckoutOrderService $checkout) {}

    /** @return array<string, mixed> */
    public function execute(CheckoutData $data, int $userId): array
    {
        return $this->checkout->preview($data, $userId);
    }
}
