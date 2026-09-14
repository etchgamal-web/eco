<?php
namespace App\Modules\Customer\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class CartItemRequest extends FormRequest {
 use AuthorizesRequest;
 public function authorize():bool{return $this->authorizePermission('customer.cart.manage');}
 public function rules():array{return $this->isMethod('delete')?['variant_id'=>['nullable','integer','exists:product_variants,id']]:['product_id'=>['required','integer','exists:products,id'],'variant_id'=>['nullable','integer','exists:product_variants,id'],'quantity'=>['required','integer','min:1','max:100']];}
}
