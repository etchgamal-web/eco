<?php

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ContentWriteRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('cms.manage');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:article,guide,faq,comparison'],
            'title' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:2000'], 'body' => ['nullable', 'string'], 'status' => ['sometimes', 'in:draft,published'], 'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:320'], 'canonical_url' => ['nullable', 'url', 'max:2048'], 'featured_image' => ['nullable', 'url', 'max:2048'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'], 'parent_id' => ['nullable', 'integer', 'exists:content_items,id'],
            'product_ids' => ['sometimes', 'array'], 'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'category_ids' => ['sometimes', 'array'], 'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
