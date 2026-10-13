<?php

namespace App\Modules\Settings\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Public request: the storefront reads the active theme without authentication. */
final class ViewAppearanceRequest extends FormRequest
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
