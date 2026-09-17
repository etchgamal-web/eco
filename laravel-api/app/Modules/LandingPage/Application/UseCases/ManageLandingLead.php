<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class ManageLandingLead
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function list(array $filters = []): array
    {
        return $this->pages->listLeads($filters);
    }

    public function update(int $id, array $data): object
    {
        return $this->pages->updateLead($id, $data);
    }
}
