<?php
namespace App\Modules\Tax\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class TaxRuleManagementRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool { return $this->authorizePermission(str_ends_with((string) $this->route()?->getName(), '.index') || str_ends_with((string) $this->route()?->getName(), '.show') ? 'taxes.view' : (str_ends_with((string) $this->route()?->getName(), '.destroy') ? 'taxes.delete' : (str_ends_with((string) $this->route()?->getName(), '.update') ? 'taxes.update' : 'taxes.create'))); }
    public function rules(): array { return ['name' => ['sometimes','string','max:120'], 'country' => ['nullable','string','size:2'], 'state' => ['nullable','string','max:120'], 'rate' => ['sometimes','numeric','min:0','max:100'], 'is_active' => ['sometimes','boolean']]; }
}
