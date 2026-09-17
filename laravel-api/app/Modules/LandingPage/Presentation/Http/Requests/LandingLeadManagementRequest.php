<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class LandingLeadManagementRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('cms.view');
    }

    public function rules(): array
    {
        return ['landing_page_id' => 'nullable|integer', 'status' => 'nullable|in:new,contacted,qualified,converted,lost', 'assigned_to' => 'nullable|integer|exists:users,id', 'notes' => 'nullable|string|max:5000'];
    }
}
