<?php

namespace App\Modules\Settlement\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SettlementListRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('shipping.view');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('has_discrepancy') && is_string($this->input('has_discrepancy'))) {
            $value = strtolower($this->input('has_discrepancy'));
            if (in_array($value, ['true', 'false'], true)) {
                $this->merge(['has_discrepancy' => $value === 'true']);
            }
        }
    }

    public function rules(): array
    {
        return [
            'provider_code' => ['nullable', 'string', 'max:60', 'exists:shipping_providers,code'],
            'status' => ['nullable', 'string', 'in:draft,processing,completed,finalized'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
            'has_discrepancy' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,period_from,period_to,status,actual_total,difference_total'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
