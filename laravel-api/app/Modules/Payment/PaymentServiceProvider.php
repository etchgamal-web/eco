<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;
use App\Modules\Payment\Domain\Contracts\PaymobWebhookVerifierInterface;
use App\Modules\Payment\Domain\Contracts\KashierWebhookVerifierInterface;
use App\Modules\Payment\Domain\Contracts\PaymentWebhookEventRepositoryInterface;
use App\Modules\Payment\Infrastructure\Gateways\PaymentGatewayRouter;
use App\Modules\Payment\Infrastructure\Persistence\EloquentPaymentRepository;
use App\Modules\Payment\Infrastructure\Persistence\EloquentPaymentOperationRepository;
use App\Modules\Payment\Infrastructure\Persistence\EloquentOperationalDashboardReader;
use App\Modules\Payment\Infrastructure\Webhooks\PaymobWebhookVerifier;
use App\Modules\Payment\Infrastructure\Webhooks\KashierWebhookVerifier;
use App\Modules\Payment\Infrastructure\Persistence\EloquentPaymentWebhookEventRepository;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PaymentRepositoryInterface::class => EloquentPaymentRepository::class,
        PaymentGatewayInterface::class => PaymentGatewayRouter::class,
        PaymentOperationRepositoryInterface::class => EloquentPaymentOperationRepository::class,
        OperationalDashboardReaderInterface::class => EloquentOperationalDashboardReader::class,
        PaymobWebhookVerifierInterface::class => PaymobWebhookVerifier::class,
        KashierWebhookVerifierInterface::class => KashierWebhookVerifier::class,
        PaymentWebhookEventRepositoryInterface::class => EloquentPaymentWebhookEventRepository::class,
    ];
}
