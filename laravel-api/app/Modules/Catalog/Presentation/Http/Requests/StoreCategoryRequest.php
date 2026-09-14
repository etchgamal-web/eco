<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreCategoryRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('categories.create'); }
 public function rules(): array { return ['name'=>['required','string','max:255'],'slug'=>['nullable','string','max:191'],'parent_id'=>['nullable','integer','exists:categories,id'],'is_active'=>['required','boolean']]; }
}
