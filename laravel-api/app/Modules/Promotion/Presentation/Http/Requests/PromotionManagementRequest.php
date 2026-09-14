<?php
namespace App\Modules\Promotion\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
final class PromotionManagementRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool { return $this->authorizePermission(str_ends_with((string) $this->route()?->getName(), '.index') || str_ends_with((string) $this->route()?->getName(), '.show') ? 'promotions.view' : (str_ends_with((string) $this->route()?->getName(), '.destroy') ? 'promotions.delete' : (str_ends_with((string) $this->route()?->getName(), '.update') ? 'promotions.update' : 'promotions.create'))); }
    public function rules(): array
    {
        $required = $this->isMethod('post') || $this->isMethod('put') ? 'required' : 'sometimes';
        $couponId = $this->route('couponId');

        return [
            'code' => [$required, 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type' => [$required, 'in:percent,fixed'],
            'value' => [$required, 'integer', 'min:1'],
            'minimum_order_amount' => ['sometimes', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'percent' && (int) $this->input('value', 0) > 100) {
                $validator->errors()->add('value', 'Percentage coupons cannot exceed 100.');
            }
        });
    }
}
