<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::pluck('id', 'hr_ref_id');
        $departments = Department::pluck('id', 'hr_ref_id');
        $designations = Designation::pluck('id', 'hr_ref_id');
        $offices = OfficeLocation::pluck('id', 'name');
        $statuses = EmployeeStatus::pluck('id', 'hr_ref_id'); // 1 Regular, 2 Probationary, 3 On Leave, 4 Resigned

        $officeNames = [
            'ma' => 'Makati HQ', 'bgc' => 'BGC Tower', 'ceb' => 'Cebu Business Park',
            'lag' => 'Sta. Rosa Hub', 'dav' => 'Davao Branch', 'clk' => 'Clark Office',
        ];

        $employees = [
            ['code' => 'EMP-00021', 'first' => 'Andrea', 'middle' => 'Molina', 'last' => 'Reyes', 'birthday' => '07-24', 'desig' => 301, 'dept' => 201, 'company' => 102, 'viber' => '+63 917 214 5589', 'office' => 'bgc', 'status' => 1, 'hired' => '2021-03-15', 'regularized' => '2021-06-15', 'about' => 'Leads product design for internal tools across the Group. Believes great software should feel invisible.', 'supervisor' => null],
            ['code' => 'EMP-00034', 'first' => 'Miguel', 'middle' => 'Santos', 'last' => 'Torres', 'birthday' => '11-02', 'desig' => 303, 'dept' => 203, 'company' => 102, 'viber' => '+63 918 322 7741', 'office' => 'bgc', 'status' => 1, 'hired' => '2022-01-10', 'regularized' => '2022-04-10', 'about' => "Keeps the Group's hardware and helpdesk running smoothly.", 'supervisor' => null],
            ['code' => 'EMP-00007', 'first' => 'Jasmine', 'middle' => 'Uy', 'last' => 'Cruz', 'birthday' => '08-02', 'desig' => 307, 'dept' => 207, 'company' => 101, 'viber' => '+63 919 442 8810', 'office' => 'ma', 'status' => 1, 'hired' => '2019-08-01', 'regularized' => '2019-11-01', 'about' => 'Runs brand and campaign strategy for Cherry Mobile across the archipelago.', 'supervisor' => null],
            ['code' => 'EMP-00045', 'first' => 'Rafael', 'middle' => 'Ong', 'last' => 'Bautista', 'birthday' => '03-14', 'desig' => 304, 'dept' => 202, 'company' => 102, 'viber' => '+63 917 553 2290', 'office' => 'bgc', 'status' => 1, 'hired' => '2020-11-20', 'regularized' => '2021-02-20', 'about' => 'Full-stack engineer focused on internal platforms and API infrastructure.', 'supervisor' => null],
            ['code' => 'EMP-00012', 'first' => 'Patricia', 'middle' => 'Yu', 'last' => 'Lim', 'birthday' => '09-19', 'desig' => 309, 'dept' => 204, 'company' => 101, 'viber' => '+63 917 220 6631', 'office' => 'ma', 'status' => 1, 'hired' => '2018-05-06', 'regularized' => '2018-08-06', 'about' => 'Partners with department heads across Cherry Mobile on hiring and people programs.', 'supervisor' => null],
            ['code' => 'EMP-00061', 'first' => 'Enzo', 'middle' => 'Ramirez', 'last' => 'Villanueva', 'birthday' => '12-05', 'desig' => 313, 'dept' => 206, 'company' => 106, 'viber' => '+63 920 114 7723', 'office' => 'ceb', 'status' => 3, 'hired' => '2022-06-13', 'regularized' => '2022-09-13', 'about' => 'Covers key accounts across the Visayas region.', 'supervisor' => 'EMP-00066'],
            ['code' => 'EMP-00052', 'first' => 'Camille', 'middle' => 'Torres', 'last' => 'Santos', 'birthday' => '01-27', 'desig' => 315, 'dept' => 205, 'company' => 105, 'viber' => '+63 917 774 3321', 'office' => 'ma', 'status' => 1, 'hired' => '2021-09-01', 'regularized' => '2021-12-01', 'about' => 'Supports budgeting and forecasting for Cherry Financial Services.', 'supervisor' => 'EMP-00090'],
            ['code' => 'EMP-00003', 'first' => 'Diego', 'middle' => 'Marasigan', 'last' => 'Fernandez', 'birthday' => '07-26', 'desig' => 317, 'dept' => 210, 'company' => 104, 'viber' => '+63 918 552 9087', 'office' => 'lag', 'status' => 1, 'hired' => '2017-02-14', 'regularized' => '2017-05-14', 'about' => 'Oversees day-to-day fulfillment operations at the Sta. Rosa hub.', 'supervisor' => null],
            ['code' => 'EMP-00019', 'first' => 'Isabel', 'middle' => 'Cortez', 'last' => 'Ramos', 'birthday' => '07-28', 'desig' => 311, 'dept' => 208, 'company' => 101, 'viber' => '+63 917 663 4471', 'office' => 'ma', 'status' => 1, 'hired' => '2019-03-22', 'regularized' => '2019-06-22', 'about' => 'Designs support workflows so customers always reach the right person fast.', 'supervisor' => null],
            ['code' => 'EMP-00028', 'first' => 'Nathaniel', 'middle' => 'Bermudez', 'last' => 'Ong', 'birthday' => '05-30', 'desig' => 320, 'dept' => 209, 'company' => 103, 'viber' => '+63 917 900 2245', 'office' => 'bgc', 'status' => 1, 'hired' => '2020-01-06', 'regularized' => '2020-04-06', 'about' => 'Reviews contracts and compliance matters for Cherry Realty developments.', 'supervisor' => null],
            ['code' => 'EMP-00071', 'first' => 'Bianca', 'middle' => 'Domingo', 'last' => 'Aquino', 'birthday' => '10-11', 'desig' => 302, 'dept' => 201, 'company' => 102, 'viber' => '+63 919 337 1102', 'office' => 'bgc', 'status' => 1, 'hired' => '2023-02-27', 'regularized' => '2023-05-27', 'about' => 'Designs the visual language for Group-wide internal tools.', 'supervisor' => 'EMP-00021'],
            ['code' => 'EMP-00044', 'first' => 'Marco', 'middle' => 'Villaruel', 'last' => 'Dela Peña', 'birthday' => '02-08', 'desig' => 305, 'dept' => 202, 'company' => 102, 'viber' => '+63 917 664 5591', 'office' => 'bgc', 'status' => 1, 'hired' => '2021-07-19', 'regularized' => '2021-10-19', 'about' => 'Owns the sync services that keep every Group system talking to each other.', 'supervisor' => null],
            ['code' => 'EMP-00007B', 'first' => 'Sofia', 'middle' => 'Reyes', 'last' => 'Mendoza', 'birthday' => '04-17', 'desig' => 310, 'dept' => 204, 'company' => 101, 'viber' => '+63 918 226 7734', 'office' => 'ma', 'status' => 1, 'hired' => '2022-10-03', 'regularized' => '2023-01-03', 'about' => 'Sources and screens talent for corporate roles across the Group.', 'supervisor' => 'EMP-00012'],
            ['code' => 'EMP-00066', 'first' => 'Julian', 'middle' => 'Torres', 'last' => 'Castillo', 'birthday' => '06-25', 'desig' => 314, 'dept' => 206, 'company' => 106, 'viber' => '+63 917 552 8834', 'office' => 'ceb', 'status' => 1, 'hired' => '2016-11-11', 'regularized' => '2017-02-11', 'about' => 'Leads the Visayas–Mindanao retail sales team.', 'supervisor' => null],
            ['code' => 'EMP-00090', 'first' => 'Kristine', 'middle' => 'Alvarez', 'last' => 'Navarro', 'birthday' => '12-19', 'desig' => 316, 'dept' => 205, 'company' => 105, 'viber' => '+63 917 330 9982', 'office' => 'ma', 'status' => 1, 'hired' => '2019-01-15', 'regularized' => '2019-04-15', 'about' => "Manages the books for Cherry Financial Services and its lending arm.", 'supervisor' => null],
            ['code' => 'EMP-00058', 'first' => 'Adrian', 'middle' => 'Cruz', 'last' => 'Gomez', 'birthday' => '03-03', 'desig' => 318, 'dept' => 210, 'company' => 104, 'viber' => '+63 918 774 2210', 'office' => 'lag', 'status' => 3, 'hired' => '2018-08-20', 'regularized' => '2018-11-20', 'about' => 'Runs inbound and outbound operations at the Sta. Rosa warehouse.', 'supervisor' => 'EMP-00003'],
            ['code' => 'EMP-00081', 'first' => 'Ella', 'middle' => 'Marquez', 'last' => 'Tan', 'birthday' => '09-09', 'desig' => 308, 'dept' => 207, 'company' => 101, 'viber' => '+63 919 221 3345', 'office' => 'ma', 'status' => 1, 'hired' => '2023-05-08', 'regularized' => '2023-08-08', 'about' => 'Handles social content and brand campaigns for Cherry Mobile.', 'supervisor' => 'EMP-00007'],
            ['code' => 'EMP-00037', 'first' => 'Gabriel', 'middle' => 'Serrano', 'last' => 'Rivera', 'birthday' => '08-15', 'desig' => 321, 'dept' => 211, 'company' => 103, 'viber' => '+63 917 663 8820', 'office' => 'bgc', 'status' => 1, 'hired' => '2020-09-14', 'regularized' => '2020-12-14', 'about' => 'Manages tenant relations and facilities across Cherry Realty properties.', 'supervisor' => null],
            ['code' => 'EMP-00099', 'first' => 'Renz', 'middle' => 'Aba', 'last' => 'Aguilar', 'birthday' => '01-30', 'desig' => 306, 'dept' => 202, 'company' => 102, 'viber' => '+63 920 445 1189', 'office' => 'bgc', 'status' => 2, 'hired' => '2026-07-06', 'regularized' => null, 'about' => "Recently joined the platform team, ramping up on the Group's internal tools.", 'supervisor' => 'EMP-00021'],
            ['code' => 'EMP-00101', 'first' => 'Faith', 'middle' => 'Olivar', 'last' => 'Delgado', 'birthday' => '11-22', 'desig' => 312, 'dept' => 208, 'company' => 101, 'viber' => '+63 918 221 7765', 'office' => 'ma', 'status' => 2, 'hired' => '2026-07-15', 'regularized' => null, 'about' => 'New addition to the CX floor team, handling frontline inquiries.', 'supervisor' => 'EMP-00019'],
            ['code' => 'EMP-00102', 'first' => 'Karlo', 'middle' => 'Fajardo', 'last' => 'Manalo', 'birthday' => '05-05', 'desig' => 319, 'dept' => 210, 'company' => 104, 'viber' => '+63 919 664 2210', 'office' => 'lag', 'status' => 2, 'hired' => '2026-06-28', 'regularized' => null, 'about' => 'Supports receiving and dispatch at the Sta. Rosa hub.', 'supervisor' => 'EMP-00003'],
        ];

        $emailFor = fn (string $first, string $last, int $companyHrRef) => match ($companyHrRef) {
            101 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherrymobile.onecherry.group',
            102 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherrydigital.onecherry.group',
            103 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherryrealty.onecherry.group',
            104 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherrylogistics.onecherry.group',
            105 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherryfinancial.onecherry.group',
            106 => strtolower($first.'.'.str_replace(' ', '', $last)).'@cherryretail.onecherry.group',
        };

        foreach ($employees as $data) {
            $employee = Employee::updateOrCreate(
                ['employee_id' => $data['code']],
                [
                    'first_name' => $data['first'],
                    'middle_name' => $data['middle'],
                    'last_name' => $data['last'],
                    'username' => strtolower($data['first'].'.'.str_replace(' ', '', $data['last'])),
                    'email' => $emailFor($data['first'], $data['last'], $data['company']),
                    'company_id' => $companies[$data['company']],
                    'department_id' => $departments[$data['dept']],
                    'designation_id' => $designations[$data['desig']],
                    'employee_status_id' => $statuses[$data['status']],
                    'is_active' => true,
                    'date_hired' => $data['hired'],
                    'date_regularized' => $data['regularized'],
                    'last_synced_at' => now(),
                ],
            );

            $employee->profile()->updateOrCreate(['employee_id' => $employee->id], [
                'birthday' => '2000-'.$data['birthday'],
                'viber_number' => $data['viber'],
                'office_location_id' => $offices[$officeNames[$data['office']]],
                'about_me' => $data['about'],
            ]);
        }

        // Second pass: resolve supervisors now that every employee_id exists.
        foreach ($employees as $data) {
            if (! $data['supervisor']) {
                continue;
            }

            $employee = Employee::where('employee_id', $data['code'])->first();
            $supervisor = Employee::where('employee_id', $data['supervisor'])->first();

            if ($employee && $supervisor) {
                $employee->update(['immediate_supervisor_id' => $supervisor->id]);
            }
        }
    }
}
