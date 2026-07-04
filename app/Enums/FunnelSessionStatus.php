<?php

namespace App\Enums;

enum FunnelSessionStatus: string
{
    case Active = 'active';
    case Converted = 'converted';
    case Abandoned = 'abandoned';
}
