<?php
namespace App\Modules\Customer\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class CartAccessRequest extends FormRequest {use AuthorizesRequest;public function authorize():bool{return $this->authorizePermission('customer.cart.view');}public function rules():array{return [];}}
