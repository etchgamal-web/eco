<?php

namespace App\Modules\Shipping\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ShippingRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $permission = match ($this->route()?->getName()) {
            'customer.shipping-methods.index', 'customer.shipments.index' => 'customer.orders.view',
            'customer.shipments.store' => 'customer.orders.manage',
            'shipping-methods.index', 'shipping-methods.show' => 'shipping.view',
            'shipping-methods.store', 'shipping-methods.update', 'shipping-methods.destroy', 'shipments.status' => 'shipping.manage',
            default => 'shipping.view',
        };

        return $this->authorizePermission($permission);
    }

    public function rules(): array
    {
        if (str_starts_with((string) $this->route()?->getName(), 'shipping-methods.')) {
            return [
                'code' => ['sometimes', 'string', 'max:60'], 'name' => ['sometimes', 'string', 'max:191'],
                'carrier' => ['nullable', 'string', 'max:191'], 'base_fee' => ['sometimes', 'integer', 'min:0'],
                'currency' => ['sometimes', 'string', 'size:3'], 'is_active' => ['sometimes', 'boolean'],
            ];
        }
        if ($this->route()?->getName() === 'customer.shipments.store') {
            return ['shipping_method_id' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'string', 'max:100']];
        }
        if ($this->route()?->getName() === 'shipments.status') {
            return ['status' => ['required', 'string', 'in:pending,picked_up,in_transit,out_for_delivery,delivered,cancelled'], 'note' => ['nullable', 'string', 'max:1000']];
        }

        return [];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('idempotency_key') && $this->header('Idempotency-Key') !== null) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }
}
