<?php
namespace App\Modules\Catalog\Infrastructure\Persistence;
use App\Models\Attribute;
use App\Modules\Catalog\Domain\ValueObjects\AttributeData;
use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\AttributeNotFoundException;
use Illuminate\Support\Collection;
class EloquentAttributeRepository implements AttributeRepositoryInterface
{
    public function all(): iterable { return Attribute::query()->with('values')->orderBy('name')->get(); }
    public function findOrFail(int $id): object
    {
        $model = Attribute::query()->with('values')->find($id);
        if ($model === null) throw new AttributeNotFoundException($id);
        return $model;
    }
    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        return Attribute::query()->where('name', $name)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists();
    }
    public function create(AttributeData $data): object { return Attribute::query()->create($data->toArray())->load('values'); }
    public function update(int $attributeId, AttributeData $data): object { $attribute = $this->findOrFail($attributeId); $attribute->update($data->toArray()); return $attribute->refresh()->load('values'); }
    public function isUsed(int $attributeId): bool { return $this->findOrFail($attributeId)->values()->whereHas('variants')->exists(); }
    public function delete(int $attributeId): void { $this->findOrFail($attributeId)->delete(); }
}
