<?php

namespace App\Modules\AI\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SocialReplyAiRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('social.conversations.manage');
    }

    public function rules(): array
    {
        return ['message' => 'required|string|max:5000', 'channel' => 'required|in:facebook,instagram,whatsapp', 'language' => 'nullable|string|max:20', 'tone' => 'nullable|string|max:80', 'context' => 'nullable|array'];
    }
}
