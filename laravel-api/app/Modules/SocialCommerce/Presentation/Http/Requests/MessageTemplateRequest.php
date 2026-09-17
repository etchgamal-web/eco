<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class MessageTemplateRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission(in_array($this->route()?->getName(), ['social.templates.index', 'social.templates.preview'], true)
            ? 'social.templates.view'
            : 'social.templates.manage');
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'sometimes|required|string|max:120',
            'channel' => 'nullable|in:facebook,instagram,whatsapp',
            'body' => 'sometimes|required|string|max:5000',
            'variables' => 'nullable|array',
            'variables.*' => 'string|regex:/^[a-zA-Z0-9_]+$/',
            'is_active' => 'sometimes|boolean',
            'values' => 'nullable|array',
        ];

        if ($this->isMethod('POST') && ! $this->route('template')) {
            $rules['name'] = 'required|string|max:120';
            $rules['body'] = 'required|string|max:5000';
        }

        return $rules;
    }
}
