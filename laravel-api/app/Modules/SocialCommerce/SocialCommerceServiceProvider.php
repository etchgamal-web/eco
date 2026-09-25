<?php

namespace App\Modules\SocialCommerce;

use App\Modules\Shared\Application\Outbox\OutboxEventHandlerInterface;
use App\Modules\SocialCommerce\Application\Outbox\SocialCommerceOutboxHandler;
use App\Modules\SocialCommerce\Domain\Contracts\AutomationRuleRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Infrastructure\Persistence\EloquentAutomationRuleRepository;
use App\Modules\SocialCommerce\Infrastructure\Persistence\EloquentMessageTemplateRepository;
use App\Modules\SocialCommerce\Infrastructure\Persistence\EloquentSocialConnectionRepository;
use App\Modules\SocialCommerce\Infrastructure\Persistence\EloquentSocialInteractionRepository;
use App\Modules\SocialCommerce\Infrastructure\Providers\SocialMessagingProviderRouter;
use Illuminate\Support\ServiceProvider;

final class SocialCommerceServiceProvider extends ServiceProvider
{
    public array $bindings = [SocialConnectionRepositoryInterface::class => EloquentSocialConnectionRepository::class, SocialInteractionRepositoryInterface::class => EloquentSocialInteractionRepository::class, SocialMessagingProviderInterface::class => SocialMessagingProviderRouter::class, AutomationRuleRepositoryInterface::class => EloquentAutomationRuleRepository::class, MessageTemplateRepositoryInterface::class => EloquentMessageTemplateRepository::class];

    public function register(): void
    {
        $this->app->tag(SocialCommerceOutboxHandler::class, OutboxEventHandlerInterface::class);
    }
}
