<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreAttributeValueRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('products.create'); }
 public function rules(): array { return ['value'=>['required','string','max:255']]; }
}
