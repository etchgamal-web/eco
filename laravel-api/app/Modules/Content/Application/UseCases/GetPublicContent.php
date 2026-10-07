<?php

namespace App\Modules\Content\Application\UseCases;

use App\Modules\Content\Domain\Contracts\ContentRepositoryInterface;

final class GetPublicContent
{
    public function __construct(private readonly ContentRepositoryInterface $content) {}

    public function execute(string $slug): object
    {
        return $this->content->findPublicBySlug($slug);
    }
}
