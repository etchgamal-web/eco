<?php
namespace App\Modules\Tax\Infrastructure\Persistence;
use App\Models\TaxRule;
use App\Modules\Tax\Domain\Contracts\TaxRuleRepositoryInterface;
use App\Modules\Settings\Domain\Exceptions\SettingsNotFoundException;
final class EloquentTaxRuleRepository implements TaxRuleRepositoryInterface
{
    public function list(): iterable { return TaxRule::query()->latest()->get(); }
    public function find(int $id): object { $rule = TaxRule::query()->find($id); if ($rule === null) throw new SettingsNotFoundException('Tax rule not found.'); return $rule; }
    public function create(array $data): object { return TaxRule::query()->create($this->normalize($data)); }
    public function update(int $id, array $data): object { $rule = $this->find($id); $rule->update($this->normalize($data)); return $rule->fresh(); }
    public function delete(int $id): void { $this->find($id)->delete(); }
    private function normalize(array $data): array { if (isset($data['country'])) $data['country'] = strtoupper($data['country']); return $data; }
}
