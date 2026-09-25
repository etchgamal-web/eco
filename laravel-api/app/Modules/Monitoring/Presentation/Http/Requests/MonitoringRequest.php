<?php

namespace App\Modules\Monitoring\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class MonitoringRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $name = (string) $this->route()?->getName();

        return $this->authorizePermission($this->isMethod('get') ? ($name === 'settings.order-monitoring.index' ? 'settings.view' : 'orders.view') : 'settings.update');
    }

    public function rules(): array
    {
        return ['rule_type' => ['sometimes', 'string', 'max:60', 'in:review_overdue,processing_overdue,shipment_no_update,delivery_overdue,settlement_missing,return_settlement_missing'], 'days' => ['sometimes', 'integer', 'min:0', 'max:365'], 'is_enabled' => ['sometimes', 'boolean'], 'search' => ['nullable', 'string', 'max:100'], 'delay_type' => ['nullable', 'string', 'max:60'], 'severity' => ['nullable', 'in:low,medium,high'], 'status' => ['nullable', 'in:open,acknowledged,resolved'], 'provider' => ['nullable', 'string', 'max:60'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
