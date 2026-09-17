<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LandingLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'nullable|string|max:180', 'email' => 'nullable|email|max:191', 'phone' => 'required_without:email|nullable|string|max:40', 'message' => 'nullable|string|max:5000', 'metadata' => 'nullable|array', 'source' => 'nullable|string|max:120'];
    }
}
