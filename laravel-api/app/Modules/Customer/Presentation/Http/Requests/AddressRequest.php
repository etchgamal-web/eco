<?php
namespace App\Modules\Customer\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class AddressRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool{return $this->authorizePermission($this->isMethod('get')?'customer.addresses.view':'customer.addresses.manage');}
 public function rules(): array{return $this->isMethod('get')||$this->isMethod('delete')?[]:['label'=>['sometimes','string','max:50'],'recipient_name'=>['required','string','max:255'],'phone'=>['required','string','max:30'],'address_line1'=>['required','string','max:255'],'address_line2'=>['nullable','string','max:255'],'city'=>['required','string','max:100'],'state'=>['nullable','string','max:100'],'postal_code'=>['nullable','string','max:30'],'country'=>['required','string','size:2'],'is_default'=>['sometimes','boolean']];}
}
