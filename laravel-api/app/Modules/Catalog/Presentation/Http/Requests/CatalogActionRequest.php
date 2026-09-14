<?php
namespace App\Modules\Catalog\Presentation\Http\Requests;

use App\Modules\Auth\Presentation\Http\Concerns\AuthorizesRequest;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class CatalogActionRequest extends FormRequest
{
    use AuthorizesRequest;

    public function authorize(): bool
    {
        $routeName = preg_replace('/^v1\./', '', (string) $this->route()?->getName());
        if ($this->isMethod('GET') && in_array($routeName, [
            'products.index', 'products.show', 'products.variants.index', 'products.variants.show',
        ], true)) {
            return true;
        }
        $permission = match ($routeName) {
            'products.index', 'products.show',
            'products.variants.index', 'products.variants.show',
            'attributes.index', 'attributes.show',
            'attributes.values.index', 'attributes.values.show' => 'products.view',
            'products.destroy', 'products.variants.destroy',
            'attributes.destroy', 'attributes.values.destroy' => 'products.delete',
            'brands.index', 'brands.show' => 'brands.view',
            'brands.destroy' => 'brands.delete',
            'categories.index', 'categories.show' => 'categories.view',
            'categories.destroy' => 'categories.delete',
            default => throw new LogicException('Catalog route is missing an authorization mapping.'),
        };

        return $this->authorizePermission($permission);
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'type' => ['sometimes', 'nullable', 'in:simple,variable'],
            'status' => ['sometimes', 'nullable', 'in:active,inactive,draft'],
            'min_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:min_price'],
            'sort' => ['sometimes', 'in:newest,price_asc,price_desc,name_asc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
