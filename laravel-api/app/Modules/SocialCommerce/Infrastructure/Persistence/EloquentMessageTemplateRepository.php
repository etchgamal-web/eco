<?php

namespace App\Modules\SocialCommerce\Infrastructure\Persistence;

use App\Models\SocialMessageTemplate;
use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;

final class EloquentMessageTemplateRepository implements MessageTemplateRepositoryInterface
{
    public function find(int $id): object
    {
        return SocialMessageTemplate::query()->findOrFail($id);
    }
}
