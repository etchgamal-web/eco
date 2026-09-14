<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateProductVariantRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('products.update'); }
 public function rules(): array { return ['sku'=>['required','string','max:191'],'price'=>['required','integer','min:0'],'compare_at_price'=>['nullable','integer','min:0'],'weight'=>['nullable','numeric','min:0'],'status'=>['required','string','max:50'],'variant_data'=>['nullable','array'],'attribute_value_ids'=>['required','array','min:1'],'attribute_value_ids.*'=>['required','integer','distinct','exists:attribute_values,id']]; }
}
