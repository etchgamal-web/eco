<?php

namespace App\Modules\Shipping\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublicTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tracking_token' => $this->route('tracking_token')]);
    }

    public function rules(): array
    {
        return ['tracking_token' => ['required', 'string', 'size:48']];
    }
}
