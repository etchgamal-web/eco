<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class LandingPageReadRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission($this->route()?->getName() === 'landing.index' ? 'cms.view' : 'cms.manage');
    }

    public function rules(): array
    {
        return ['status' => 'nullable|in:draft,published'];
    }
}
