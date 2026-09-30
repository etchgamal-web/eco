<?php

namespace App\Modules\Order\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class OrderNoteRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('orders.manage');
    }

    public function rules(): array
    {
        return ['notes' => ['required', 'string', 'max:5000']];
    }
}
