<?php

namespace App\Modules\Content\Application\UseCases;

use App\Modules\Content\Domain\Contracts\ContentRepositoryInterface;

final class ListPublicContent
{
    public function __construct(private readonly ContentRepositoryInterface $content) {}
    public function execute(?string $type = null, int $page = 1, int $perPage = 20): array
    {
        return $this->content->listPublic($type, $page, $perPage);
    }
}
