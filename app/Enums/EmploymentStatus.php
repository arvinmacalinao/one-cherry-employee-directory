<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active Employee',
            self::OnLeave => 'On Leave',
            self::Resigned => 'Resigned',
            self::Inactive => 'Inactive',
        };
    }

    /**
     * Statuses that appear in the public/employee-facing directory.
     * Resigned and Inactive employees are hidden by default.
     */
    public static function visibleInDirectory(): array
    {
        return [self::Active, self::OnLeave];
    }
}
