<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class ProductReviewRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool
    {
        return $this->authorizePermission(match ($this->route()?->getName()) {
            'customer.reviews.store' => 'customer.orders.manage',
            'customer.reviews.index' => 'customer.orders.view',
            'reviews.index' => 'reviews.view',
            'reviews.moderate' => 'reviews.manage',
            default => 'reviews.view',
        });
    }
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'customer.reviews.store' => ['variant_id' => ['nullable','integer','exists:product_variants,id'], 'rating' => ['required','integer','min:1','max:5'], 'title' => ['nullable','string','max:160'], 'body' => ['nullable','string','max:5000']],
            'reviews.moderate' => ['status' => ['required','in:approved,rejected']],
            default => [],
        };
    }
}
