<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SocialReplyRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('social.conversations.manage');
    }

    public function rules(): array
    {
        return ['body' => 'required|string|max:5000'];
    }
}
