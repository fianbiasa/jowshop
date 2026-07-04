<?php

namespace App\Enums;

enum OfferStage: string
{
    case Bump = 'bump';
    case Upsell = 'upsell';
    case Downsell = 'downsell';
}
