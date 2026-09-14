<?php

namespace App\Modules\Settings\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('settings.update');
    }

    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:191'],
            'value' => [
                'present',
                Rule::when($this->input('type') === 'string', ['nullable', 'string']),
                Rule::when($this->input('type') === 'boolean', ['boolean']),
                Rule::when($this->input('type') === 'integer', ['integer']),
                Rule::when($this->input('type') === 'float', ['numeric']),
                Rule::when($this->input('type') === 'json', ['array']),
                Rule::when(in_array($this->input('key'), ['cart.abandoned_scan_time', 'backup.schedule'], true), ['date_format:H:i']),
                Rule::when($this->input('key') === 'backup.retention_days', ['min:1', 'max:3650']),
            ],
            'type' => ['required', Rule::in(['string', 'boolean', 'integer', 'float', 'json'])],
            'description' => ['nullable', 'string'],
            'is_secret' => ['sometimes', 'boolean'],
            'is_encrypted' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('key') !== null) {
            $this->merge(['key' => $this->route('key')]);
        }
    }
}
