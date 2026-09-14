<?php
namespace App\Modules\Customer\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class ProductRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize():bool{return $this->isMethod('get')?$this->authorizePermission('customer.wishlist.view'):$this->authorizePermission('customer.wishlist.manage');}
 public function rules():array{return $this->isMethod('get')||$this->isMethod('delete')?[]:['product_id'=>['required','integer','exists:products,id']];}
}
