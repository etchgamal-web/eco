<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreProductRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('products.create'); }
 public function rules(): array { return ['name'=>['required','string','max:255'],'slug'=>['nullable','string','max:191'],'description'=>['nullable','string'],'type'=>['required',Rule::in(['simple','variable'])],'status'=>['required','string','max:50'],'brand_id'=>['nullable','integer','exists:brands,id'],'category_id'=>['nullable','integer','exists:categories,id']]; }
}
