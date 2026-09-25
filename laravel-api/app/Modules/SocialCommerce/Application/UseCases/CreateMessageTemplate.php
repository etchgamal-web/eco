<?php

namespace App\Modules\SocialCommerce\Application\UseCases;

use App\Modules\SocialCommerce\Domain\Contracts\MessageTemplateRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final class CreateMessageTemplate
{
    public function __construct(private readonly MessageTemplateRepositoryInterface $templates) {}

    public function execute(array $data): object
    {
        $variables = $this->variables($data['body']);
        if (isset($data['variables']) && array_diff($variables, $data['variables'])) {
            throw new SocialCommerceException('Template variables must declare every placeholder used in the body.');
        }

        return $this->templates->create([
            'name' => $data['name'],
            'channel' => $data['channel'] ?? null,
            'body' => $data['body'],
            'variables' => array_values($data['variables'] ?? $variables),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    private function variables(string $body): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $body, $matches);

        return array_values(array_unique($matches[1]));
    }
}
