<?php

namespace App\Modules\SocialCommerce\Domain\Enums;

enum ConversationMode: string
{
    case MANUAL = 'manual';
    case AUTOMATED = 'automated';
    case PAUSED = 'paused';
}
