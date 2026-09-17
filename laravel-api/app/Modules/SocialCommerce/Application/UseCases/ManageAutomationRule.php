<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\AutomationRuleRepositoryInterface;

final class ManageAutomationRule
{
    public function __construct(private readonly AutomationRuleRepositoryInterface $rules) {}

    public function list(array $filters = []): array
    {
        return $this->rules->list($filters);
    }

    public function saveRule(array $data): object
    {
        return $this->rules->create($data);
    }

    public function update(int $id, array $data): object
    {
        return $this->rules->update($this->rules->find($id), $data);
    }

    public function removeRule(int $id): void
    {
        $this->rules->delete($this->rules->find($id));
    }
}
