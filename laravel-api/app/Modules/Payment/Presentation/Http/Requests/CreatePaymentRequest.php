<?php

namespace App\Modules\Payment\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class CreatePaymentRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('customer.orders.manage');
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:cash_on_delivery,paymob,kashier'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('idempotency_key') && $this->header('Idempotency-Key') !== null) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }
}
