<?php
namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Domain\ValueObjects\AttributeData;
use App\Modules\Catalog\Domain\ValueObjects\AttributeValueData;
use App\Modules\Catalog\Application\UseCases\Attributes\CreateAttribute;
use App\Modules\Catalog\Application\UseCases\Attributes\CreateAttributeValue;
use App\Modules\Catalog\Application\UseCases\Attributes\DeleteAttribute;
use App\Modules\Catalog\Application\UseCases\Attributes\DeleteAttributeValue;
use App\Modules\Catalog\Application\UseCases\Attributes\GetAttribute;
use App\Modules\Catalog\Application\UseCases\Attributes\GetAttributeValue;
use App\Modules\Catalog\Application\UseCases\Attributes\ListAttributes;
use App\Modules\Catalog\Application\UseCases\Attributes\ListAttributeValues;
use App\Modules\Catalog\Application\UseCases\Attributes\UpdateAttribute;
use App\Modules\Catalog\Application\UseCases\Attributes\UpdateAttributeValue;
use App\Modules\Catalog\Presentation\Http\Requests\CatalogActionRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreAttributeRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreAttributeValueRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateAttributeRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateAttributeValueRequest;
use Illuminate\Http\JsonResponse;

class AttributeController extends Controller
{
    public function index(CatalogActionRequest $request, ListAttributes $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function store(StoreAttributeRequest $request, CreateAttribute $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute(AttributeData::fromArray($request->validated()))], 201);
    }

    public function show(CatalogActionRequest $request, int $attribute, GetAttribute $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($attribute)]);
    }

    public function update(UpdateAttributeRequest $request, int $attribute, UpdateAttribute $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($attribute, AttributeData::fromArray($request->validated()))]);
    }

    public function destroy(CatalogActionRequest $request, int $attribute, DeleteAttribute $useCase): JsonResponse
    {
        $useCase->execute($attribute);

        return response()->json(null, 204);
    }

    public function values(CatalogActionRequest $request, int $attribute, ListAttributeValues $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($attribute)]);
    }

    public function storeValue(StoreAttributeValueRequest $request, int $attribute, CreateAttributeValue $useCase): JsonResponse
    {
        $data = $request->validated();
        $data['attribute_id'] = $attribute;

        return response()->json(['data' => $useCase->execute(AttributeValueData::fromArray($data))], 201);
    }

    public function showValue(CatalogActionRequest $request, int $attribute, int $value, GetAttributeValue $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($attribute, $value)]);
    }

    public function updateValue(UpdateAttributeValueRequest $request, int $attribute, int $value, UpdateAttributeValue $useCase): JsonResponse
    {
        $data = $request->validated();
        $data['attribute_id'] = $attribute;

        return response()->json(['data' => $useCase->execute($attribute, $value, AttributeValueData::fromArray($data))]);
    }

    public function destroyValue(CatalogActionRequest $request, int $attribute, int $value, DeleteAttributeValue $useCase): JsonResponse
    {
        $useCase->execute($attribute, $value);

        return response()->json(null, 204);
    }
}
