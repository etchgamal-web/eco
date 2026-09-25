<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;
use App\Modules\SocialCommerce\Domain\ValueObjects\RenderedTemplate;

final class PreviewMessageTemplate
{
    public function __construct(private readonly MessageTemplateRepositoryInterface $templates) {}

    public function execute(int $id, array $variables): array
    {
        $template = $this->templates->find($id);

        return [
            'template_id' => $template->id,
            'text' => RenderedTemplate::render($template->body, $variables)->text,
            'variables' => $template->variables ?? [],
        ];
    }
}
