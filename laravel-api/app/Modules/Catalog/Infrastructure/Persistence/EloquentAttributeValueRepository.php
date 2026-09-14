<?php
namespace App\Modules\Catalog\Infrastructure\Persistence;
use App\Models\AttributeValue;
use App\Modules\Catalog\Domain\ValueObjects\AttributeValueData;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\AttributeValueNotFoundException;
use Illuminate\Support\Collection;
class EloquentAttributeValueRepository implements AttributeValueRepositoryInterface
{
    public function forAttribute(int $attributeId): iterable { return AttributeValue::query()->where('attribute_id', $attributeId)->orderBy('value')->get(); }
    public function findOrFail(int $id, ?int $attributeId = null): object
    {
        $model = AttributeValue::query()->with('attribute')->whereKey($id)->when($attributeId, fn ($q) => $q->where('attribute_id', $attributeId))->first();
        if ($model === null) throw new AttributeValueNotFoundException($id);
        return $model;
    }
    public function findMany(array $ids): iterable { return AttributeValue::query()->whereKey($ids)->orderBy('id')->get(); }
    public function valueExists(int $attributeId, string $value, ?int $exceptId = null): bool
    {
        return AttributeValue::query()->where('attribute_id', $attributeId)->where('value', $value)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
    }
    public function create(AttributeValueData $data): object { return AttributeValue::query()->create($data->toArray())->load('attribute'); }
    public function update(int $valueId, AttributeValueData $data): object { $value = $this->findOrFail($valueId); $value->update($data->toArray()); return $value->refresh()->load('attribute'); }
    public function isUsed(int $valueId): bool { return $this->findOrFail($valueId)->variants()->exists(); }
    public function delete(int $valueId): void { $this->findOrFail($valueId)->delete(); }
}
