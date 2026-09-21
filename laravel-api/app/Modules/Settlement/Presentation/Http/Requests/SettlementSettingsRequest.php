<?php
namespace App\Modules\Settlement\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class SettlementSettingsRequest extends FormRequest { use AuthorizesRequest; public function authorize():bool{return $this->authorizePermission($this->isMethod('get')?'settings.view':'settings.update');} public function rules():array{return $this->isMethod('get')?[]:['key'=>['required','string','in:settlement_import.max_rows,settlement_import.tolerance,settlement_import.allow_duplicates,settlement_import.auto_finalize,settlement_import.currency,settlement_import.notify_mismatch'],'value'=>['required']];} }
