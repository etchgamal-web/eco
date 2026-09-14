<?php

namespace App\Modules\Customer;

use App\Modules\Customer\Domain\Contracts\AddressRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CartRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CustomerNotificationRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CustomerOrderRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CustomerPreferencesRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Domain\Contracts\WishlistRepositoryInterface;
use App\Modules\Customer\Infrastructure\Console\MarkAbandonedCarts;
use App\Modules\Customer\Infrastructure\Persistence\EloquentAddressRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentCartRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentCustomerNotificationRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentCustomerOrderRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentCustomerPreferencesRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentCustomerRepository;
use App\Modules\Customer\Infrastructure\Persistence\EloquentWishlistRepository;
use Illuminate\Support\ServiceProvider;

final class CustomerServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CustomerRepositoryInterface::class => EloquentCustomerRepository::class,
        AddressRepositoryInterface::class => EloquentAddressRepository::class,
        CartRepositoryInterface::class => EloquentCartRepository::class,
        CustomerOrderRepositoryInterface::class => EloquentCustomerOrderRepository::class,
        WishlistRepositoryInterface::class => EloquentWishlistRepository::class,
        CustomerPreferencesRepositoryInterface::class => EloquentCustomerPreferencesRepository::class,
        CustomerNotificationRepositoryInterface::class => EloquentCustomerNotificationRepository::class,
    ];

    public function boot(): void
    {
        $this->commands([MarkAbandonedCarts::class]);
    }
}
