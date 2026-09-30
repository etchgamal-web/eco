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
            'payments.index', 'payments.show' => 'payments.view',
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
            default => [],
        };
    }
}
