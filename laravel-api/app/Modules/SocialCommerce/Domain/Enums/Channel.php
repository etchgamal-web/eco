<?php

namespace App\Modules\SocialCommerce\Domain\Enums;

enum Channel: string
{
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case WHATSAPP = 'whatsapp';
}
