<?php

namespace App\Modules\SocialCommerce\Domain\Enums;

enum MessageDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
}
