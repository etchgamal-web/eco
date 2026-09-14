<?php

namespace App\Modules\Customer\Domain\Exceptions;

use RuntimeException;

final class CustomerNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Customer {$id} was not found.");
    }
}
