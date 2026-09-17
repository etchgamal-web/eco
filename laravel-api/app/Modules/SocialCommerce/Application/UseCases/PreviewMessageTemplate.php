<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Models\SocialMessageTemplate;
use App\Modules\SocialCommerce\Domain\ValueObjects\RenderedTemplate;

final class PreviewMessageTemplate
{
    public function execute(int $id, array $variables): array
    {
        $template = SocialMessageTemplate::query()->findOrFail($id);

        return [
            'template_id' => $template->id,
            'text' => RenderedTemplate::render($template->body, $variables)->text,
            'variables' => $template->variables ?? [],
        ];
    }
}
