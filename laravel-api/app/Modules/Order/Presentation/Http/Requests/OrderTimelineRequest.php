<?php

namespace App\Modules\Order\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OrderTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
