<?php

namespace App\Enums;

enum PaymentEnvironment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';
}
