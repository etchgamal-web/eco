<?php

namespace App\Modules\Shipping\Infrastructure\Providers;

use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;
use App\Modules\Shipping\Infrastructure\Configuration\ShippingProviderSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class BostaShippingProvider implements ShippingProviderInterface
{
    public function __construct(private readonly ShippingProviderSettings $settings)
    {
    }

    public function supports(object $shipment): bool
    {
        return strtolower((string) ($shipment->method->carrier ?? $shipment->method_code)) === 'bosta'
            && $this->settings->enabled('bosta', (bool) config('services.bosta.enabled', false));
    }

    public function create(object $shipment): array
    {
        $apiKey = (string) $this->settings->value('bosta', 'api_key', config('services.bosta.api_key'));
        if ($apiKey === '') {
            throw new ShippingException('Bosta is not configured.');
        }

        $address = (array) $shipment->address_snapshot;
        $name = trim((string) ($address['recipient_name'] ?? $shipment->user?->name ?? 'Customer'));
        $parts = preg_split('/\s+/', $name, 2) ?: ['Customer'];
        $cod = data_get($shipment->metadata, 'cod');
        if ($cod === null) {
            $cod = strtolower((string) ($shipment->order->payments->first()?->method ?? '')) === 'cash_on_delivery'
                ? (int) $shipment->order->total_amount
                : 0;
        }

        $payload = [
            'type' => (int) $this->settings->value('bosta', 'delivery_type', 10),
            'cod' => (int) $cod,
            'businessReference' => (string) $shipment->idempotency_key,
            'notes' => (string) data_get($shipment->metadata, 'notes', ''),
            'webhookUrl' => $this->settings->value('bosta', 'webhook_url', config('services.bosta.webhook_url')),
            'receiver' => [
                'firstName' => $parts[0] ?: 'Customer',
                'lastName' => $parts[1] ?? 'Customer',
                'phone' => (string) ($address['phone'] ?? $shipment->user?->phone ?? ''),
                'email' => (string) ($shipment->user?->email ?? 'customer@example.com'),
            ],
            'dropOffAddress' => [
                'city' => (string) data_get($shipment->metadata, 'dropoff_city_code', data_get($address, 'city', '')),
                'zone' => (string) data_get($shipment->metadata, 'dropoff_zone', data_get($address, 'state', '')),
                'firstLine' => (string) data_get($address, 'address_line1', ''),
                'buildingNumber' => (int) data_get($shipment->metadata, 'building_number', 0),
            ],
            'specs' => [
                'packageDetails' => [
                    'description' => 'Order ' . $shipment->order_id,
                    'itemsCount' => (int) ($shipment->order->items->sum('quantity') ?: 1),
                ],
                'packageType' => (string) $this->settings->value('bosta', 'package_type', 'Small'),
            ],
        ];

        $response = $this->client($apiKey)->post('/api/v2/deliveries?apiVersion=1', $payload)->throw()->json();
        $data = (array) ($response['data'] ?? []);
        if (($response['success'] ?? false) !== true || ($data['_id'] ?? '') === '') {
            throw new ShippingException((string) ($response['message'] ?? 'Bosta delivery creation failed.'));
        }

        return [
            'status' => 'pending',
            'tracking_number' => (string) ($data['trackingNumber'] ?? $data['_id']),
            'metadata' => [
                'provider' => 'bosta',
                'provider_reference' => $data['_id'],
                'bosta_delivery_id' => $data['_id'],
                'bosta_response' => $response,
            ],
        ];
    }

    public function track(object $shipment): array
    {
        $tracking = (string) $shipment->tracking_number;
        if ($tracking === '') {
            throw new ShippingException('Bosta tracking number is missing.');
        }
        return $this->client((string) $this->settings->value('bosta', 'api_key', config('services.bosta.api_key')))
            ->post('/api/v2/deliveries/search', ['trackingNumbers' => $tracking])->throw()->json();
    }

    public function cancel(object $shipment): array
    {
        $deliveryId = (string) data_get($shipment->metadata, 'bosta_delivery_id', '');
        if ($deliveryId === '') {
            throw new ShippingException('Bosta delivery reference is missing.');
        }
        return $this->client((string) $this->settings->value('bosta', 'api_key', config('services.bosta.api_key')))
            ->delete('/api/v2/deliveries/business/' . rawurlencode($deliveryId) . '/terminate')->throw()->json();
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->settings->value('bosta', 'base_url', config('services.bosta.base_url', 'https://app.bosta.co')), '/'))
            ->acceptJson()->asJson()->withHeaders(['Authorization' => $apiKey, 'X-Requested-By' => 'ecommerce-platform'])
            ->timeout((int) $this->settings->value('bosta', 'timeout', config('services.bosta.timeout', 15)))
            ->retry(2, 250, throw: false);
    }
}
