<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;
use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
final class ProductMediaRequest extends FormRequest
{
    use AuthorizesRequest;
    public function authorize(): bool
    {
        return $this->authorizePermission($this->isMethod('get') ? 'products.view' : ($this->isMethod('delete') ? 'products.delete' : 'products.update'));
    }
    public function rules(): array
    {
        if ($this->isMethod('get') || $this->isMethod('delete')) return [];
        return $this->isMethod('post') ? ['file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:max_width=6000,max_height=6000']] : ['sort_order' => ['required', 'integer', 'min:0']];
    }
}
