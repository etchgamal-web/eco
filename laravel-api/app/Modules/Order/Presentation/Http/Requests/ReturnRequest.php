<?php
namespace App\Modules\Order\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class ReturnRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool { return $this->authorizePermission(str_starts_with((string) $this->route()?->getName(), 'customer.returns') ? ($this->isMethod('get') ? 'customer.orders.view' : 'customer.orders.manage') : ($this->route()?->getName() === 'returns.index' ? 'orders.view' : 'orders.return')); }
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'customer.returns.store' => ['reason' => ['required','string','max:120'], 'notes' => ['nullable','string','max:2000'], 'items' => ['required','array','min:1'], 'items.*.order_item_id' => ['required','integer','min:1'], 'items.*.quantity' => ['required','integer','min:1']],
            'returns.reject' => ['reason' => ['required','string','max:2000']],
            default => [],
        };
    }
}
