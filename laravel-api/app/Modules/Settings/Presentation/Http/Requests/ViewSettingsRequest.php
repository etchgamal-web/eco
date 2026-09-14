<?php
namespace App\Modules\Settings\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;

final class ViewSettingsRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        return $this->authorizePermission('settings.view');
    }

    public function rules(): array
    {
        return [];
    }
}
