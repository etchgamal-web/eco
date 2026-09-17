<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ConversationRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('social.conversations.manage');
    }

    public function rules(): array
    {
        if (in_array($this->route()?->getName(), ['social.conversations.show', 'social.conversations.messages', 'social.conversations.pause', 'social.conversations.resume', 'social.conversations.manual'], true)) {
            return [];
        }

        return ['body' => 'required|string|max:4000'];
    }
}
