<?php

namespace App\Modules\LandingPage\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LandingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['event_type' => 'required|in:view,conversion,cta_click', 'session_id' => 'nullable|string|max:120', 'source' => 'nullable|string|max:120', 'medium' => 'nullable|string|max:120', 'campaign' => 'nullable|string|max:120', 'content' => 'nullable|string|max:120', 'term' => 'nullable|string|max:120', 'referrer' => 'nullable|string|max:2048', 'metadata' => 'nullable|array'];
    }
}
