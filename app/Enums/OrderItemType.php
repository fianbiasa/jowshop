<?php

namespace App\Enums;

enum OrderItemType: string
{
    case Main = 'main';
    case Bump = 'bump';
    case Upsell = 'upsell';
    case Downsell = 'downsell';
}
