<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'required_without:phone', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
