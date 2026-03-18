<?php

namespace App\Enums;

enum KeyStatus: string
{
    case ACTIVE = 'active';
    case RETIRED = 'retired';
    case REVOKED = 'revoked';
}
