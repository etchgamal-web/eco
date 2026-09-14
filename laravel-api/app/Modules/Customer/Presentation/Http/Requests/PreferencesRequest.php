<?php
namespace App\Modules\Customer\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class PreferencesRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize():bool{return $this->authorizePermission('customer.preferences.manage');}
 public function rules():array{return $this->isMethod('get')?[]:['data'=>['required','array']];}
}
