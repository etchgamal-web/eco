<?php
namespace App\Modules\Settlement\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class FinalizeSettlementRequest extends FormRequest { use AuthorizesRequest; public function authorize():bool{return $this->authorizePermission('shipping.manage');} public function rules():array{return [];} }
