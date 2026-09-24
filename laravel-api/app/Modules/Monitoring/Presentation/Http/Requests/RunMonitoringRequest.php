<?php

namespace App\Modules\Monitoring\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class RunMonitoringRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('monitoring.run');
    }

    public function rules(): array
    {
        return [];
    }
}
