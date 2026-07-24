<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';
}
