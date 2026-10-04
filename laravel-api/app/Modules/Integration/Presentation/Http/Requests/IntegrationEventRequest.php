<?php

namespace App\Modules\Integration\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class IntegrationEventRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->isMethod('get') ? 'integrations.view' : 'integrations.retry');
    }

    public function rules(): array
    {
        return ['source' => ['nullable', 'in:payment,shipping,social'], 'status' => ['nullable', 'string', 'max:30'], 'provider' => ['nullable', 'string', 'max:60']];
    }
}
