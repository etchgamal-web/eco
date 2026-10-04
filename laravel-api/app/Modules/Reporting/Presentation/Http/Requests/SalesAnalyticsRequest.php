<?php

namespace App\Modules\Reporting\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SalesAnalyticsRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('reports.view');
    }

    public function rules(): array
    {
        return ['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'], 'compare' => ['nullable', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $to = $this->input('to', now()->format('Y-m-d'));
        $this->merge(['from' => $this->input('from', now()->subDays(29)->format('Y-m-d')), 'to' => $to]);
    }
}
