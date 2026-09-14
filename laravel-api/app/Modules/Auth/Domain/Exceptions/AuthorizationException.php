<?php

namespace App\Modules\Auth\Domain\Exceptions;

use RuntimeException;

final class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'You are not authorized to perform this action.')
    {
        parent::__construct($message);
    }
}
