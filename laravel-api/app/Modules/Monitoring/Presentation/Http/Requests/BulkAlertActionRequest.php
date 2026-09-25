<?php

namespace App\Modules\Monitoring\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class BulkAlertActionRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('orders.manage');
    }

    public function rules(): array
    {
        return [
            'alert_ids' => ['required', 'array', 'min:1', 'max:100'],
            'alert_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
