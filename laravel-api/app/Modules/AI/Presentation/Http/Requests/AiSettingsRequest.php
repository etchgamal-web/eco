<?php

namespace App\Modules\AI\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class AiSettingsRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->route()?->getName() === 'ai.settings.show' ? 'settings.view' : 'settings.update');
    }

    public function rules(): array
    {
        return ['provider_order' => 'sometimes|array|min:1', 'provider_order.*' => 'in:gemini,openai,groq,openrouter', 'primary_model' => 'sometimes|string|max:160', 'fallback_enabled' => 'sometimes|boolean', 'cost_optimization' => 'sometimes|boolean', 'context_awareness' => 'sometimes|boolean'];
    }
}
