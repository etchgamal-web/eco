<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface MessageTemplateRepositoryInterface
{
    public function find(int $id): object;
}
