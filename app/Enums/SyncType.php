<?php

namespace App\Enums;

enum SyncType: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
}
