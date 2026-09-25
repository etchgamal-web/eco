<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;

final class ListMessageTemplates
{
    public function __construct(private readonly MessageTemplateRepositoryInterface $templates) {}

    public function execute(array $filters = []): array
    {
        return $this->templates->list($filters);
    }
}
