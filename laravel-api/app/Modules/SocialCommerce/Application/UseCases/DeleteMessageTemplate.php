<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Models\SocialMessageTemplate;

final class DeleteMessageTemplate
{
    public function execute(int $id): void
    {
        SocialMessageTemplate::query()->findOrFail($id)->delete();
    }
}
