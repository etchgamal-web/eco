<?php

namespace App\Modules\Settings\Domain\Exceptions;

use RuntimeException;

final class SettingsNotFoundException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Setting [{$key}] was not found.");
    }
}
