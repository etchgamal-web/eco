<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Infrastructure\Gateways\KashierGateway;
use App\Modules\Payment\Infrastructure\Gateways\PaymobGateway;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PaymentGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_paymob_intention_converts_order_lines_and_shipping_from_pounds_to_piasters(): void
    {
        config([
            'services.paymob.base_url' => 'https://accept.paymob.com',
            'services.paymob.secret_key' => 'test-secret',
            'services.paymob.public_key' => 'test-public',
            'services.paymob.integration_ids' => [12345],
            'services.paymob.notification_url' => 'https://store.example.test/api/v1/webhooks/paymob',
            'services.paymob.redirection_url' => 'https://store.example.test/checkout/success',
        ]);
        Http::fake(['https://accept.paymob.com/v1/intention/' => Http::response(['id' => 'intent-1', 'client_secret' => 'client-secret-1'], 201)]);

        app(PaymobGateway::class)->createPayment($this->orderForProvider(), 'paymob', 'payment-key-1');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://accept.paymob.com/v1/intention/'
            && $request['amount'] === 250000
            && $request['items'][0]['amount'] === 200000
            && $request['items'][1]['amount'] === 50000
            && $request['currency'] === 'EGP');
    }

    public function test_paymob_reconciliation_checks_cents_and_currency_before_confirming(): void
    {
        config(['services.paymob.base_url' => 'https://accept.paymob.com', 'services.paymob.secret_key' => 'test-secret']);
        Http::fake(['https://accept.paymob.com/api/acceptance/transactions/123456' => Http::response([
            'id' => 123456,
            'amount_cents' => 150000,
            'currency' => 'EGP',
            'success' => true,
            'pending' => false,
        ], 200)]);
        $payment = (object) ['provider_reference' => 'intent-3', 'metadata' => ['transaction_id' => '123456'], 'amount' => 1500, 'currency' => 'EGP'];

        $result = app(PaymobGateway::class)->reconcilePayment($payment);

        $this->assertSame('confirmed', $result['status']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://accept.paymob.com/api/acceptance/transactions/123456');
    }

    public function test_paymob_reconciliation_rejects_a_success_with_the_wrong_amount(): void
    {
        config(['services.paymob.base_url' => 'https://accept.paymob.com', 'services.paymob.secret_key' => 'test-secret']);
        Http::fake(['https://accept.paymob.com/api/acceptance/transactions/123456' => Http::response([
            'id' => 123456,
            'amount_cents' => 149999,
            'currency' => 'EGP',
            'success' => true,
            'pending' => false,
        ], 200)]);
        $payment = (object) ['provider_reference' => 'intent-3', 'metadata' => ['transaction_id' => '123456'], 'amount' => 1500, 'currency' => 'EGP'];

        $this->expectException(PaymentAmountMismatchException::class);
        app(PaymobGateway::class)->reconcilePayment($payment);
    }

    public function test_kashier_payment_session_sends_the_stored_major_currency_amount(): void
    {
        config([
            'services.kashier.api_base_url' => 'https://test-api.kashier.io',
            'services.kashier.merchant_id' => 'MID-TEST-001',
            'services.kashier.secret_key' => 'test-secret',
            'services.kashier.payment_api_key' => 'test-payment-key',
            'services.kashier.redirect_url' => 'https://store.example.test/checkout/success',
            'services.kashier.webhook_url' => 'https://store.example.test/api/v1/webhooks/kashier',
        ]);
        Http::fake(['https://test-api.kashier.io/v3/payment/sessions' => Http::response([
            '_id' => 'session-1',
            'sessionUrl' => 'https://payments.kashier.io/session/session-1?mode=test',
        ], 200)]);

        app(KashierGateway::class)->createPayment($this->orderForProvider(), 'kashier', 'payment-key-2');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://test-api.kashier.io/v3/payment/sessions'
            && $request['amount'] === '2500.00'
            && $request['currency'] === 'EGP'
            && $request['order'] === 'payment-key-2');
    }

    public function test_kashier_reconciliation_uses_the_documented_session_payment_endpoint(): void
    {
        config([
            'services.kashier.api_base_url' => 'https://test-api.kashier.io',
            'services.kashier.secret_key' => 'test-secret',
            'services.kashier.payment_api_key' => 'test-payment-key',
        ]);
        Http::fake(['https://test-api.kashier.io/v3/payment/sessions/session-1/payment' => Http::response([
            'message' => 'success',
            'data' => ['sessionId' => 'session-1', 'status' => 'SUCCESS', 'amount' => '2500.00', 'currency' => 'EGP'],
        ], 200)]);
        $payment = (object) ['provider_reference' => 'session-1', 'metadata' => [], 'amount' => 2500, 'currency' => 'EGP'];

        $result = app(KashierGateway::class)->reconcilePayment($payment);

        $this->assertSame('confirmed', $result['status']);
        $this->assertSame('session-1', $result['provider_reference']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://test-api.kashier.io/v3/payment/sessions/session-1/payment');
    }

    public function test_kashier_modern_webhook_uses_header_signature_and_is_idempotent(): void
    {
        config(['services.kashier.payment_api_key' => 'test-payment-key']);
        $user = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 2500,
            'subtotal_amount' => 2500,
            'currency' => 'EGP',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'method' => 'kashier',
            'provider_reference' => 'session-1',
            'amount' => 2500,
            'currency' => 'EGP',
            'status' => 'provider_created',
            'idempotency_key' => 'payment-key-3',
        ]);
        PaymentOperation::query()->create([
            'payment_id' => $payment->id,
            'operation' => 'create',
            'status' => 'provider_created',
            'idempotency_key' => 'payment-key-3',
            'attempt_count' => 1,
        ]);
        $signatureKeys = ['amount', 'currency', 'kashierOrderId', 'merchantOrderId', 'status', 'transactionId'];
        $data = [
            'amount' => 2500,
            'currency' => 'EGP',
            'kashierOrderId' => 'kashier-order-1',
            'merchantOrderId' => 'payment-key-3',
            'status' => 'SUCCESS',
            'transactionId' => 'TX-0001',
            'signatureKeys' => $signatureKeys,
        ];
        $payload = ['event' => 'pay', 'data' => $data];
        $signature = $this->kashierSignature($data, $signatureKeys);

        $this->postJson('/api/v1/webhooks/kashier', $payload, ['x-kashier-signature' => $signature])
            ->assertOk()->assertJsonPath('received', true);
        $this->postJson('/api/v1/webhooks/kashier', $payload, ['x-kashier-signature' => $signature])
            ->assertStatus(409)->assertJsonPath('duplicate', true);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payment_operations', ['payment_id' => $payment->id, 'operation' => 'create', 'status' => 'confirmed']);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_paymob_webhook_compares_the_provider_piaster_amount_with_the_major_unit_payment(): void
    {
        config(['services.paymob.hmac_secret' => 'test-hmac-secret']);
        $user = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 1500,
            'subtotal_amount' => 1500,
            'currency' => 'EGP',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'method' => 'paymob',
            'provider_reference' => 'intent-2',
            'amount' => 1500,
            'currency' => 'EGP',
            'status' => 'provider_created',
            'idempotency_key' => 'payment-key-4',
        ]);
        PaymentOperation::query()->create([
            'payment_id' => $payment->id,
            'operation' => 'create',
            'status' => 'provider_created',
            'idempotency_key' => 'payment-key-4',
            'attempt_count' => 1,
        ]);
        $payload = ['obj' => [
            'amount_cents' => 150000,
            'created_at' => '2026-10-06T00:00:00Z',
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => 123456,
            'integration_id' => 12345,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => ['id' => 456789, 'merchant_order_id' => 'payment-key-4'],
            'owner' => 1,
            'pending' => false,
            'source_data' => ['pan' => '0008', 'sub_type' => 'Mastercard', 'type' => 'card'],
            'success' => true,
        ]];
        $hmac = $this->paymobHmac($payload['obj']);
        $this->postJson('/api/v1/webhooks/paymob?hmac='.$hmac, $payload)
            ->assertOk()->assertJsonPath('received', true);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'pending']);
    }

    private function orderForProvider(): object
    {
        return (object) [
            'id' => 101,
            'user_id' => 7,
            'user' => null,
            'items' => [(object) ['name' => 'Product', 'total_amount' => 2000, 'sku' => 'SKU-1', 'quantity' => 1]],
            'shipping_amount' => 500,
            'total_amount' => 2500,
            'currency' => 'EGP',
            'shipping_address' => ['recipient_name' => 'Test Customer', 'address_line1' => 'Test St', 'phone' => '+201000000000'],
        ];
    }

    private function kashierSignature(array $data, array $keys): string
    {
        sort($keys, SORT_STRING);
        $parts = array_map(static function (string $field) use ($data): string {
            $value = is_bool($data[$field]) ? ($data[$field] ? 'true' : 'false') : (string) $data[$field];

            return $field.'='.rawurlencode($value);
        }, $keys);

        return hash_hmac('sha256', implode('&', $parts), 'test-payment-key');
    }

    private function paymobHmac(array $object): string
    {
        $order = (array) ($object['order'] ?? []);
        $source = (array) ($object['source_data'] ?? []);
        $values = [
            'amount_cents' => $object['amount_cents'] ?? '',
            'created_at' => $object['created_at'] ?? '',
            'currency' => $object['currency'] ?? '',
            'error_occured' => $object['error_occured'] ?? '',
            'has_parent_transaction' => $object['has_parent_transaction'] ?? '',
            'id' => $object['id'] ?? '',
            'integration_id' => $object['integration_id'] ?? '',
            'is_3d_secure' => $object['is_3d_secure'] ?? '',
            'is_auth' => $object['is_auth'] ?? '',
            'is_capture' => $object['is_capture'] ?? '',
            'is_refunded' => $object['is_refunded'] ?? '',
            'is_standalone_payment' => $object['is_standalone_payment'] ?? '',
            'is_voided' => $object['is_voided'] ?? '',
            'order.id' => $order['id'] ?? '',
            'owner' => $object['owner'] ?? '',
            'pending' => $object['pending'] ?? '',
            'source_data.pan' => $source['pan'] ?? '',
            'source_data.sub_type' => $source['sub_type'] ?? '',
            'source_data.type' => $source['type'] ?? '',
            'success' => $object['success'] ?? '',
        ];
        ksort($values);
        $message = implode('', array_map(static fn ($value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, $values));

        return hash_hmac('sha512', $message, 'test-hmac-secret');
    }
}
