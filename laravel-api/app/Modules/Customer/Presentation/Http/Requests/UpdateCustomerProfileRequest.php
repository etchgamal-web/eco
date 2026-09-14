<?php

namespace App\Modules\Customer\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCustomerProfileRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('customer.profile.update');
    }

    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
        ];
    }
}
