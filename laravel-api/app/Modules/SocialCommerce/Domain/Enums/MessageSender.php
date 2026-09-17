<?php

namespace App\Modules\SocialCommerce\Domain\Enums;

enum MessageSender: string
{
    case CUSTOMER = 'customer';
    case STORE = 'store';
    case AUTOMATION = 'automation';
}
