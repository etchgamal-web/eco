<?php
namespace App\Modules\Settlement\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class SettlementReadRequest extends FormRequest { use AuthorizesRequest; public function authorize():bool{return $this->authorizePermission('shipping.view');} public function rules():array{return [];} }
