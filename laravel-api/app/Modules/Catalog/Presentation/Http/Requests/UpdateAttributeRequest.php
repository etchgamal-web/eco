<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateAttributeRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('products.update'); }
 public function rules(): array { return ['name'=>['required','string','max:255']]; }
}
