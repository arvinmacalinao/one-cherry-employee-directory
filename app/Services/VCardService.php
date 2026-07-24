<?php

namespace App\Services;

use App\Models\Employee;

class VCardService
{
    public function generate(Employee $employee): string
    {
        $profile = $employee->profile;

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "N:{$employee->last_name};{$employee->first_name};{$employee->middle_name};;{$profile?->suffix}",
            "FN:{$employee->full_name}",
            "TITLE:{$employee->designation?->name}",
            "ORG:{$employee->company?->name};{$employee->department?->name}",
            "EMAIL;TYPE=WORK:{$employee->email}",
        ];

        if ($profile?->personal_email) {
            $lines[] = "EMAIL;TYPE=HOME:{$profile->personal_email}";
        }

        if ($profile?->mobile_number) {
            $lines[] = "TEL;TYPE=CELL:{$profile->mobile_number}";
        }

        if ($profile?->telephone) {
            $extension = $profile->local_extension ? ";ext={$profile->local_extension}" : '';
            $lines[] = "TEL;TYPE=WORK,VOICE:{$profile->telephone}{$extension}";
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines);
    }

    public function filename(Employee $employee): string
    {
        return "{$employee->first_name}-{$employee->last_name}.vcf";
    }
}
