<?php

namespace App\Modules\SocialCommerce\Domain\ValueObjects;

use App\Modules\SocialCommerce\Domain\Exceptions\SocialCommerceException;

final readonly class RenderedTemplate
{
    private function __construct(public string $text) {}

    public static function render(string $template, array $variables): self
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $template, $matches);
        foreach (array_unique($matches[1]) as $key) {
            if (! array_key_exists($key, $variables)) {
                throw new SocialCommerceException("Missing template variable: {$key}");
            }
        } $text = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', fn ($m) => (string) $variables[$m[1]], $template);

        return new self($text);
    }
}
