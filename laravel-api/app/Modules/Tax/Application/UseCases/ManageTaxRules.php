<?php
namespace App\Modules\Tax\Application\UseCases;
use App\Modules\Tax\Domain\Contracts\TaxRuleRepositoryInterface;
final class ManageTaxRules
{
    public function __construct(private readonly TaxRuleRepositoryInterface $rules) {}
    public function list(): iterable { return $this->rules->list(); }
    public function show(int $id): object { return $this->rules->find($id); }
    public function store(array $data): object { return $this->rules->create($data); }
    public function update(int $id, array $data): object { return $this->rules->update($id, $data); }
    public function remove(int $id): void { $this->rules->delete($id); }
}
