<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublicLandingPageRequest extends FormRequest
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
