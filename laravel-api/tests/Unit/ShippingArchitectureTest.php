<?php

namespace Tests\Unit;

use App\Modules\Shipping\Application\UseCases\ProcessBostaWebhook;
use App\Modules\Shipping\Domain\Contracts\ShippingWebhookEventRepositoryInterface;
use App\Modules\Shipping\Infrastructure\Persistence\EloquentShippingWebhookEventRepository;
use Tests\TestCase;

final class ShippingArchitectureTest extends TestCase
{
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
