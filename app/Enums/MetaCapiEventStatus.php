<?php

namespace App\Enums;

enum MetaCapiEventStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
