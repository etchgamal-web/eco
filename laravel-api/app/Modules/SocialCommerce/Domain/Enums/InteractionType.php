<?php

namespace App\Modules\SocialCommerce\Domain\Enums;

enum InteractionType: string
{
    case COMMENT = 'comment';
    case MESSAGE = 'message';
    case REACTION = 'reaction';
    case MENTION = 'mention';
    case WEBHOOK_EVENT = 'webhook_event';
}
