<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreBrandRequest extends FormRequest
{
 use AuthorizesRequest;
 public function authorize(): bool { return $this->authorizePermission('brands.create'); }
 public function rules(): array { return ['name'=>['required','string','max:255'],'slug'=>['nullable','string','max:191'],'status'=>['required','string','max:50']]; }
}
