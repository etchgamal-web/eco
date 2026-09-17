<?php

namespace App\Modules\AI\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ProductAiRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('products.create');
    }

    public function rules(): array
    {
        return ['brief' => 'required|string|max:5000', 'language' => 'nullable|string|max:20', 'keywords' => 'nullable|array', 'keywords.*' => 'string|max:80', 'tone' => 'nullable|string|max:80'];
    }
}
