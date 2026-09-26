<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $this->authenticatedUser();

        return true;
    }

    public function rules(): array
    {
        $userId = $this->authenticatedUser()->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }
}
