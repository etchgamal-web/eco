<?php

namespace App\Modules\Staff\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

final class StaffRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->isMethod('get') ? 'assistants.view' : ($this->isMethod('post') ? 'assistants.create' : ($this->isMethod('delete') ? 'assistants.delete' : 'assistants.update')));
    }

    public function rules(): array
    {
        if ($this->isMethod('get') || $this->isMethod('delete')) {
            return [];
        }

        $id = $this->route('staffId');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'required_without:phone', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email', Rule::unique('users', 'phone')->ignore($id)],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'slug')->where(fn (Builder $query) => $query->where('is_active', true)),
                Rule::notIn(['customer']),
            ],
        ];
    }
}
