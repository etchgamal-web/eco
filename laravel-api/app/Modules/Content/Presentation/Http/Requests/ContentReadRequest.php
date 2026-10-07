<?php

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ContentReadRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool
    {
        $permission = in_array($this->route()?->getName(), [
            'content.admin.destroy', 'content.admin.publish', 'content.admin.unpublish',
        ], true) ? 'cms.manage' : 'cms.view';
        return $this->authorizePermission($permission);
    }
    public function rules(): array { return ['type' => ['sometimes', 'nullable', 'in:article,guide,faq,comparison'], 'status' => ['sometimes', 'nullable', 'in:draft,published']]; }
}
