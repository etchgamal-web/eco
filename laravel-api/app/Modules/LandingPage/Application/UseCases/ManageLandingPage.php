<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;

final class ManageLandingPage
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function list(array $f = []): array
    {
        return $this->pages->list($f);
    }

    public function persistPage(array $d, ?int $id = null): object
    {
        return $this->pages->save($d, $id);
    }

    public function remove(int $id): void
    {
        $this->pages->delete($this->pages->find($id));
    }

    public function publish(int $id): object
    {
        return $this->pages->save(['status' => 'published', 'published_at' => now()], $id);
    }

    public function unpublish(int $id): object
    {
        return $this->pages->save(['status' => 'draft', 'published_at' => null], $id);
    }
}
