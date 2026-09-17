<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Models\SocialMessageTemplate;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final class UpdateMessageTemplate
{
    public function execute(int $id, array $data): object
    {
        $template = SocialMessageTemplate::query()->findOrFail($id);
        $body = $data['body'] ?? $template->body;
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $body, $matches);
        $variables = array_values(array_unique($matches[1]));
        $declared = $data['variables'] ?? $template->variables ?? $variables;
        if (array_diff($variables, $declared)) {
            throw new SocialCommerceException('Template variables must declare every placeholder used in the body.');
        }

        $template->fill([
            'name' => $data['name'] ?? $template->name,
            'channel' => array_key_exists('channel', $data) ? $data['channel'] : $template->channel,
            'body' => $body,
            'variables' => array_values($declared),
            'is_active' => $data['is_active'] ?? $template->is_active,
        ])->save();

        return $template;
    }
}
