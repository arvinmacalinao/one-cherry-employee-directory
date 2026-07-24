<?php

namespace App\Services;

use App\Models\Employee;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    /**
     * QR codes are generated on demand, never persisted — see architecture-plan.md §3.1.
     * Encodes the employee's public profile URL so scanning opens their directory page.
     */
    public function forEmployee(Employee $employee): ResultInterface
    {
        $builder = new Builder(
            writer: new SvgWriter,
            data: route('directory.show', $employee),
            size: 240,
            margin: 8,
        );

        return $builder->build();
    }

    public function svgFor(Employee $employee): string
    {
        return $this->forEmployee($employee)->getString();
    }
}
