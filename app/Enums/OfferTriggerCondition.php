<?php

namespace App\Enums;

enum OfferTriggerCondition: string
{
    case Initial = 'initial';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
