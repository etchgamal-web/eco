<?php

namespace App\Modules\Order\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use App\Modules\Settings\Application\UseCases\GetSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class CheckoutRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return (bool) app(GetSetting::class)->execute('checkout.require_authentication', true) === false;
        }
        return $this->authorizePermission('customer.orders.manage');
    }

    public function rules(): array
    {
        $rules = [
            'address_id' => ['nullable', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'shipping_method_id' => ['nullable', 'integer', 'min:1', 'required_with:shipping_idempotency_key'],
            'shipping_idempotency_key' => ['nullable', 'string', 'max:100', 'required_with:shipping_method_id'],
            'payment_method' => ['nullable', 'string', 'in:cash_on_delivery,paymob,kashier'],
            'payment_idempotency_key' => ['nullable', 'string', 'max:100', 'required_with:payment_method'],
            'coupon_code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];

        if ($this->user() === null) {
            $rules['coupon_code'] = ['prohibited'];
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['items.*.product_id'] = ['required', 'integer', 'exists:products,id'];
            $rules['items.*.variant_id'] = ['nullable', 'integer', 'exists:product_variants,id'];
            $rules['items.*.quantity'] = ['required', 'integer', 'min:1'];
            $rules['guest'] = ['required', 'array'];
            $rules['guest.name'] = ['required', 'string', 'max:255'];
            $rules['guest.email'] = ['nullable', 'email', 'max:191'];
            $rules['guest.phone'] = ['required', 'string', 'max:30'];
            $rules['guest.address_line1'] = ['required', 'string', 'max:255'];
            $rules['guest.address_line2'] = ['nullable', 'string', 'max:255'];
            $rules['guest.city'] = ['required', 'string', 'max:120'];
            $rules['guest.state'] = ['nullable', 'string', 'max:120'];
            $rules['guest.postal_code'] = ['nullable', 'string', 'max:30'];
            $rules['guest.country'] = ['sometimes', 'string', 'size:2'];
        } else {
            $rules['address_id'][] = 'required';
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('idempotency_key') && $this->header('Idempotency-Key') !== null) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }

    protected function failedAuthorization(): void
    {
        if ($this->user() === null) {
            throw new HttpResponseException(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        parent::failedAuthorization();
    }
}
