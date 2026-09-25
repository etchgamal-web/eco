<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;

final class DeleteMessageTemplate
{
    public function __construct(private readonly MessageTemplateRepositoryInterface $templates) {}

    public function execute(int $id): void
    {
        $this->templates->delete($this->templates->find($id));
    }
}
