<?php

namespace App\Enums;

enum EmployeeSource: string
{
    case HrSync = 'hr_sync';
    case Manual = 'manual';
}
