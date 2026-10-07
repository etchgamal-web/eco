<?php

namespace App\Modules\Content\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublicContentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['type' => ['sometimes', 'nullable', 'in:article,guide,faq,comparison']]; }
}
