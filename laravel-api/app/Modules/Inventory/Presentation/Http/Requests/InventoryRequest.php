<?php
namespace App\Modules\Inventory\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;
final class InventoryRequest extends FormRequest{use AuthorizesRequest;public function authorize():bool{return $this->authorizePermission($this->isMethod('get')?'inventory.view':'inventory.manage');}public function rules():array{if($this->isMethod('get'))return [];return ['product_id'=>['required','integer','exists:products,id'],'variant_id'=>['nullable','integer','exists:product_variants,id'],'quantity'=>['required','integer','not_in:0'],'reason'=>[$this->routeIs('inventory.adjust')?'required':'nullable','string','max:100'],'note'=>['nullable','string','max:1000']];}}
