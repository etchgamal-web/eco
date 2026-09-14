<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;

class ChangePasswordRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $this->authenticatedUser();

        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
