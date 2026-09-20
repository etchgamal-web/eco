<?php

namespace Tests\Unit;

use App\Modules\Shipping\Application\UseCases\ProcessBostaWebhook;
use App\Modules\Shipping\Domain\Contracts\ShippingWebhookEventRepositoryInterface;
use App\Modules\Shipping\Domain\Exceptions\InvalidShipmentTransitionException;
use App\Modules\Shipping\Domain\StateMachines\ShipmentStateMachine;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShippingWebhookEventRepository;
use Tests\TestCase;

final class ShippingArchitectureTest extends TestCase
{
    public function test_pending_shipment_cannot_be_marked_picked_up_before_provider_creation(): void
    {
        $this->expectException(InvalidShipmentTransitionException::class);

        ShipmentStateMachine::assert('pending', 'picked_up');
    }

    public function test_bosta_webhook_use_case_depends_on_domain_contracts_not_eloquent_models(): void
    {
        $source = file_get_contents((new \ReflectionClass(ProcessBostaWebhook::class))->getFileName());

        $this->assertIsString($source);
        $this->assertStringNotContainsString('App\\Models', $source);
        $this->assertStringContainsString('ShippingWebhookEventRepositoryInterface', $source);
    }

    public function test_webhook_event_contract_is_bound_to_its_eloquent_adapter(): void
    {
        $this->assertInstanceOf(
            EloquentShippingWebhookEventRepository::class,
            $this->app->make(ShippingWebhookEventRepositoryInterface::class),
        );
    }
}
