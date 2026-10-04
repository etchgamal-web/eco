<?php

namespace App\Modules\Customer\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerAdminRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->isMethod('get') ? 'customers.view' : 'customers.update');
    }

    public function rules(): array
    {
        if ($this->isMethod('delete') && $this->route('addressId') !== null) {
            return [];
        }
        if ($this->route('addressId') !== null || ($this->isMethod('post') && $this->route('customerId') !== null)) {
            return [
                'label' => ['sometimes', 'string', 'max:80'], 'recipient_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:30'], 'address_line1' => ['required', 'string', 'max:255'],
                'address_line2' => ['nullable', 'string', 'max:255'], 'city' => ['required', 'string', 'max:120'],
                'state' => ['nullable', 'string', 'max:120'], 'postal_code' => ['nullable', 'string', 'max:30'],
                'country' => ['sometimes', 'string', 'size:2'], 'is_default' => ['sometimes', 'boolean'],
            ];
        }
        if ($this->route('customerId') !== null) {
            $customerId = $this->route('customerId');

            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'required_without:phone', Rule::unique('users', 'email')->ignore($customerId)],
                'phone' => ['nullable', 'string', 'max:30', 'required_without:email', Rule::unique('users', 'phone')->ignore($customerId)],
                'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            ];
        }

        return [
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
