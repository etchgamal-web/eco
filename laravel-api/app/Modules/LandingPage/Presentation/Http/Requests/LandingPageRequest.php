<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class LandingPageRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('cms.manage');
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:180', 'slug' => 'required|string|max:191|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'status' => 'sometimes|in:draft,published', 'excerpt' => 'nullable|string|max:1000', 'sections' => 'required|array|min:1', 'sections.*.type' => 'required|string|max:60', 'sections.*.data' => 'nullable|array', 'seo_title' => 'nullable|string|max:180', 'seo_description' => 'nullable|string|max:320', 'og_image_url' => 'nullable|url|max:2048', 'canonical_url' => 'nullable|url|max:2048', 'settings' => 'nullable|array'];
    }
}
