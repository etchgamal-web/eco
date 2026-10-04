<?php

namespace App\Modules\Payment\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class PaymentManagementRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $permission = match ($this->route()?->getName()) {
            'payments.index', 'payments.show', 'payments.global.index', 'payments.global.export' => 'payments.view',
            'customer.payments.index' => 'customer.orders.view',
            'payments.confirm' => 'payments.manage',
            'payments.refund' => 'payments.refund',
            'operations.reconcile', 'operations.outbox.retry' => 'payments.manage',
            default => 'payments.view',
        };

        return $this->authorizePermission($permission);
    }

    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'operations.reconcile' => ['operation_id' => ['required', 'integer', 'min:1']],
            'payments.global.index', 'payments.global.export' => ['status' => ['nullable', 'string', 'max:30'], 'method' => ['nullable', 'string', 'max:40'], 'provider' => ['nullable', 'string', 'max:191'], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']],
            default => [],
        };
    }
}
