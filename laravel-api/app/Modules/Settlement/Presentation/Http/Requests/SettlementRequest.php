<?php

namespace App\Modules\Settlement\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SettlementRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('shipping.manage');
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:20480'], 'provider_code' => ['required', 'string', 'max:60'], 'period_from' => ['nullable', 'date'], 'period_to' => ['nullable', 'date', 'after_or_equal:period_from']];
    }
}
