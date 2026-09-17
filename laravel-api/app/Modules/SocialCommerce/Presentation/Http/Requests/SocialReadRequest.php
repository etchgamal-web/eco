<?php

namespace App\Modules\SocialCommerce\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SocialReadRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('social.interactions.view');
    }

    public function rules(): array
    {
        return ['channel' => 'nullable|in:facebook,instagram,whatsapp', 'customer_id' => 'nullable|integer', 'product_id' => 'nullable|integer', 'interaction_type' => 'nullable|string', 'status' => 'nullable|string', 'from' => 'nullable|date', 'to' => 'nullable|date'];
    }
}
