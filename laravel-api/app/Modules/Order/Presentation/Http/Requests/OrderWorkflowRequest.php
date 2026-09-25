<?php

namespace App\Modules\Order\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class OrderWorkflowRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $permission = match ($this->route()?->getName()) {
            'orders.review' => 'orders.review',
            'orders.contact' => 'orders.contact',
            'orders.confirm' => 'orders.confirm',
            default => 'orders.manage',
        };

        return $this->authorizePermission($permission);
    }

    public function rules(): array
    {
        if ($this->route()?->getName() !== 'orders.contact') {
            return [];
        }

        return [
            'contact_result' => ['required', 'string', 'in:confirmed,modified,no_answer,rejected,unavailable'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
