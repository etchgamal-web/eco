<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class RbacRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->isMethod('get') ? $this->authorizePermission('roles.view') : $this->authorizePermission('permissions.manage');
    }

    public function rules(): array
    {
        return $this->isMethod('get') ? [] : ['permissions' => ['required', 'array', 'max:200'], 'permissions.*' => ['string', 'distinct', 'exists:permissions,slug']];
    }
}
