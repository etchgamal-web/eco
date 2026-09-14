<?php

namespace App\Modules\Shipping\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
