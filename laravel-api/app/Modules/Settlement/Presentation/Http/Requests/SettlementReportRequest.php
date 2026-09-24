<?php

namespace App\Modules\Settlement\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SettlementReportRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('shipping.view');
    }

    public function rules(): array
    {
        return [
            'provider_code' => ['nullable', 'string', 'max:60', 'exists:shipping_providers,code'],
            'status' => ['nullable', 'string', 'in:draft,processing,completed,finalized'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
