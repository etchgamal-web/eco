<?php
namespace App\Modules\Monitoring\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class AlertActionRequest extends FormRequest { use AuthorizesRequest; public function authorize():bool{return $this->authorizePermission('orders.manage');} public function rules():array{return [];} }
