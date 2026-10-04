<?php

namespace App\Modules\Staff\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class AuditLogRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->routeIs('roles.audit')
            ? $this->authorizePermission('roles.view')
            : $this->authorizePermission('assistants.view');
    }

    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50'], 'action' => ['nullable', 'string', 'max:100'], 'actor_id' => ['nullable', 'integer', 'min:1'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']];
    }
}
