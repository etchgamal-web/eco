<?php

namespace App\Modules\Shipping;

use App\Modules\Shipping\Domain\Contracts\ShippingMethodRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingRateCalculatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingWebhookAuthenticatorInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingWebhookEventRepositoryInterface;
use App\Modules\Shipping\Infrastructure\Persistence\DatabaseShippingRateCalculator;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShippingMethodRepository;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShipmentRepository;
use App\Modules\Shipping\Infrastructure\Providers\ShippingProviderRouter;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShipmentOperationRepository;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShippingWebhookEventRepository;
use App\Modules\Shipping\Infrastructure\Webhooks\ShippingWebhookAuthenticator;
use Illuminate\Support\ServiceProvider;

final class ShippingServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ShippingMethodRepositoryInterface::class => EloquentShippingMethodRepository::class,
        ShippingRateCalculatorInterface::class => DatabaseShippingRateCalculator::class,
        ShipmentRepositoryInterface::class => EloquentShipmentRepository::class,
        ShippingProviderInterface::class => ShippingProviderRouter::class,
        ShipmentOperationRepositoryInterface::class => EloquentShipmentOperationRepository::class,
        ShippingWebhookAuthenticatorInterface::class => ShippingWebhookAuthenticator::class,
        ShippingWebhookEventRepositoryInterface::class => EloquentShippingWebhookEventRepository::class,
    ];
}
