<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class AutomationRuleRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('social.automation.manage');
    }

    public function rules(): array
    {
        return ['name' => 'sometimes|required|string|max:120', 'channel' => 'nullable|in:facebook,instagram,whatsapp', 'conditions' => 'required|array', 'conditions.keywords' => 'nullable|array', 'conditions.keywords.*' => 'string|max:80', 'actions' => 'required|array|min:1', 'actions.*.type' => 'required|in:send_message,reply_to_comment', 'actions.*.message' => 'nullable|string|max:5000', 'actions.*.template_id' => 'nullable|integer|exists:social_message_templates,id', 'is_active' => 'sometimes|boolean'];
    }
}
