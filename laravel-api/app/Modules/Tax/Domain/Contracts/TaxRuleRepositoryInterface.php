<?php
namespace App\Modules\Tax\Domain\Contracts;
interface TaxRuleRepositoryInterface
{
    public function list(): iterable;
    public function find(int $id): object;
    public function create(array $data): object;
    public function update(int $id, array $data): object;
    public function delete(int $id): void;
}
