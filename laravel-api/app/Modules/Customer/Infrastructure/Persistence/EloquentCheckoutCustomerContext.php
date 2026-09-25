<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Customer\Domain\Contracts\CheckoutCustomerContextInterface;
use App\Modules\Customer\Infrastructure\Models\CustomerAddress;
use App\Modules\Customer\Infrastructure\Models\CustomerCart;

final class EloquentCheckoutCustomerContext implements CheckoutCustomerContextInterface
{
    public function cartForUser(int $userId): object
    {
        return CustomerCart::query()
            ->with(['items.product', 'items.variant'])
            ->where('user_id', $userId)
            ->firstOrCreate(
                ['user_id' => $userId],
                ['last_activity_at' => now(), 'recovery_token' => \Illuminate\Support\Str::random(64)]
            );
    }

    public function addressForUser(int $userId, int $addressId): object
    {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->findOrFail($addressId);
    }

    public function clearCartForUser(int $userId): void
    {
        $cart = CustomerCart::query()->where('user_id', $userId)->first();
        $cart?->items()->delete();
    }
}
