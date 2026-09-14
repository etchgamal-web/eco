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
            default => 'payments.view',
        };

        return $this->authorizePermission($permission);
    }

    public function rules(): array
    {
        return [];
    }
}
