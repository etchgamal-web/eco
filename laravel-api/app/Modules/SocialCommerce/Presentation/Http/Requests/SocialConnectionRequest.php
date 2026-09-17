<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SocialConnectionRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->isMethod('get') ? 'social.connections.view' : 'social.connections.manage');
    }

    public function rules(): array
    {
        if ($this->isMethod('get') && $this->route('connection')) {
            return [];
        }

        return ['channel' => 'required|in:facebook,instagram,whatsapp', 'name' => 'required|string|max:120', 'provider_account_id' => 'nullable|string|max:255', 'access_token' => 'nullable|string', 'webhook_secret' => 'nullable|string', 'metadata' => 'nullable|array', 'is_active' => 'sometimes|boolean'];
    }
}
