<?php

namespace Database\Seeders;

use App\Enums\EmployeeSource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::pluck('id', 'hr_ref_id');
        $departments = Department::pluck('id', 'hr_ref_id');
        $designations = Designation::pluck('id', 'hr_ref_id');
        $offices = OfficeLocation::pluck('id', 'name');

        $officeNames = [
            'ma' => 'Makati HQ', 'bgc' => 'BGC Tower', 'ceb' => 'Cebu Business Park',
            'lag' => 'Sta. Rosa Hub', 'dav' => 'Davao Branch', 'clk' => 'Clark Office',
        ];

        $employees = [
            ['code' => 'EMP-00021', 'first' => 'Andrea', 'middle' => 'Molina', 'last' => 'Reyes', 'nickname' => 'Andi', 'gender' => 'Female', 'birthday' => '07-24', 'desig' => 301, 'dept' => 201, 'company' => 102, 'mobile' => '+63 917 214 5589', 'viber' => '+63 917 214 5589', 'ext' => '2231', 'office' => 'bgc', 'status' => 'active', 'hired' => '2021-03-15', 'regularized' => '2021-06-15', 'skills' => ['Figma', 'Design Systems', 'User Research', 'Prototyping', 'Accessibility'], 'about' => 'Leads product design for internal tools across the Group. Believes great software should feel invisible.', 'linkedin' => 'andrea-reyes', 'supervisor' => null, 'emergency' => ['Liza Reyes', 'Mother', '+63 917 555 0110']],
            ['code' => 'EMP-00034', 'first' => 'Miguel', 'middle' => 'Santos', 'last' => 'Torres', 'nickname' => null, 'gender' => 'Male', 'birthday' => '11-02', 'desig' => 303, 'dept' => 203, 'company' => 102, 'mobile' => '+63 918 322 7741', 'viber' => '+63 918 322 7741', 'ext' => '2105', 'office' => 'bgc', 'status' => 'active', 'hired' => '2022-01-10', 'regularized' => '2022-04-10', 'skills' => ['Network Administration', 'Hardware Troubleshooting', 'Google Workspace', 'Zendesk'], 'about' => "Keeps the Group's hardware and helpdesk running smoothly.", 'linkedin' => 'miguel-torres-it', 'supervisor' => null, 'emergency' => ['Grace Torres', 'Spouse', '+63 918 555 0199']],
            ['code' => 'EMP-00007', 'first' => 'Jasmine', 'middle' => 'Uy', 'last' => 'Cruz', 'nickname' => null, 'gender' => 'Female', 'birthday' => '08-02', 'desig' => 307, 'dept' => 207, 'company' => 101, 'mobile' => '+63 919 442 8810', 'viber' => '+63 919 442 8810', 'ext' => '1140', 'office' => 'ma', 'status' => 'active', 'hired' => '2019-08-01', 'regularized' => '2019-11-01', 'skills' => ['Brand Strategy', 'Campaign Management', 'Content Marketing'], 'about' => 'Runs brand and campaign strategy for Cherry Mobile across the archipelago.', 'linkedin' => 'jasmine-cruz', 'supervisor' => null, 'emergency' => ['Noel Cruz', 'Father', '+63 919 555 0220']],
            ['code' => 'EMP-00045', 'first' => 'Rafael', 'middle' => 'Ong', 'last' => 'Bautista', 'nickname' => 'Raf', 'gender' => 'Male', 'birthday' => '03-14', 'desig' => 304, 'dept' => 202, 'company' => 102, 'mobile' => '+63 917 553 2290', 'viber' => '+63 917 553 2290', 'ext' => '2244', 'office' => 'bgc', 'status' => 'active', 'hired' => '2020-11-20', 'regularized' => '2021-02-20', 'skills' => ['Laravel', 'Vue.js', 'MySQL', 'Docker'], 'about' => 'Full-stack engineer focused on internal platforms and API infrastructure.', 'linkedin' => 'rafael-bautista', 'supervisor' => null, 'emergency' => ['Carmen Bautista', 'Mother', '+63 917 555 0330']],
            ['code' => 'EMP-00012', 'first' => 'Patricia', 'middle' => 'Yu', 'last' => 'Lim', 'nickname' => 'Trish', 'gender' => 'Female', 'birthday' => '09-19', 'desig' => 309, 'dept' => 204, 'company' => 101, 'mobile' => '+63 917 220 6631', 'viber' => '+63 917 220 6631', 'ext' => '1105', 'office' => 'ma', 'status' => 'active', 'hired' => '2018-05-06', 'regularized' => '2018-08-06', 'skills' => ['Employee Relations', 'Talent Acquisition', 'HRIS'], 'about' => 'Partners with department heads across Cherry Mobile on hiring and people programs.', 'linkedin' => 'patricia-lim-hr', 'supervisor' => null, 'emergency' => ['Bryan Lim', 'Spouse', '+63 917 555 0440']],
            ['code' => 'EMP-00061', 'first' => 'Enzo', 'middle' => 'Ramirez', 'last' => 'Villanueva', 'nickname' => null, 'gender' => 'Male', 'birthday' => '12-05', 'desig' => 313, 'dept' => 206, 'company' => 106, 'mobile' => '+63 920 114 7723', 'viber' => '+63 920 114 7723', 'ext' => '5510', 'office' => 'ceb', 'status' => 'on_leave', 'hired' => '2022-06-13', 'regularized' => '2022-09-13', 'skills' => ['B2B Sales', 'Negotiation', 'CRM'], 'about' => 'Covers key accounts across the Visayas region.', 'linkedin' => null, 'supervisor' => 'EMP-00066', 'emergency' => ['Rosa Villanueva', 'Mother', '+63 920 555 0550']],
            ['code' => 'EMP-00052', 'first' => 'Camille', 'middle' => 'Torres', 'last' => 'Santos', 'nickname' => null, 'gender' => 'Female', 'birthday' => '01-27', 'desig' => 315, 'dept' => 205, 'company' => 105, 'mobile' => '+63 917 774 3321', 'viber' => '+63 917 774 3321', 'ext' => '3312', 'office' => 'ma', 'status' => 'active', 'hired' => '2021-09-01', 'regularized' => '2021-12-01', 'skills' => ['Financial Modeling', 'Excel', 'SAP'], 'about' => 'Supports budgeting and forecasting for Cherry Financial Services.', 'linkedin' => 'camille-santos-fin', 'supervisor' => 'EMP-00090', 'emergency' => ['Paolo Santos', 'Spouse', '+63 917 555 0660']],
            ['code' => 'EMP-00003', 'first' => 'Diego', 'middle' => 'Marasigan', 'last' => 'Fernandez', 'nickname' => null, 'gender' => 'Male', 'birthday' => '07-26', 'desig' => 317, 'dept' => 210, 'company' => 104, 'mobile' => '+63 918 552 9087', 'viber' => '+63 918 552 9087', 'ext' => '6602', 'office' => 'lag', 'status' => 'active', 'hired' => '2017-02-14', 'regularized' => '2017-05-14', 'skills' => ['Supply Chain', 'Warehouse Management', 'Fleet Ops'], 'about' => 'Oversees day-to-day fulfillment operations at the Sta. Rosa hub.', 'linkedin' => 'diego-fernandez-ops', 'supervisor' => null, 'emergency' => ['Elena Fernandez', 'Spouse', '+63 918 555 0770']],
            ['code' => 'EMP-00019', 'first' => 'Isabel', 'middle' => 'Cortez', 'last' => 'Ramos', 'nickname' => 'Bel', 'gender' => 'Female', 'birthday' => '07-28', 'desig' => 311, 'dept' => 208, 'company' => 101, 'mobile' => '+63 917 663 4471', 'viber' => '+63 917 663 4471', 'ext' => '1188', 'office' => 'ma', 'status' => 'active', 'hired' => '2019-03-22', 'regularized' => '2019-06-22', 'skills' => ['Customer Support', 'Zendesk', 'Process Design'], 'about' => 'Designs support workflows so customers always reach the right person fast.', 'linkedin' => 'isabel-ramos-cx', 'supervisor' => null, 'emergency' => ['Mario Ramos', 'Father', '+63 917 555 0880']],
            ['code' => 'EMP-00028', 'first' => 'Nathaniel', 'middle' => 'Bermudez', 'last' => 'Ong', 'nickname' => 'Nate', 'gender' => 'Male', 'birthday' => '05-30', 'desig' => 320, 'dept' => 209, 'company' => 103, 'mobile' => '+63 917 900 2245', 'viber' => '+63 917 900 2245', 'ext' => '4401', 'office' => 'bgc', 'status' => 'active', 'hired' => '2020-01-06', 'regularized' => '2020-04-06', 'skills' => ['Corporate Law', 'Contracts', 'Compliance'], 'about' => 'Reviews contracts and compliance matters for Cherry Realty developments.', 'linkedin' => 'nathaniel-ong-law', 'supervisor' => null, 'emergency' => ['Michelle Ong', 'Spouse', '+63 917 555 0990']],
            ['code' => 'EMP-00071', 'first' => 'Bianca', 'middle' => 'Domingo', 'last' => 'Aquino', 'nickname' => null, 'gender' => 'Female', 'birthday' => '10-11', 'desig' => 302, 'dept' => 201, 'company' => 102, 'mobile' => '+63 919 337 1102', 'viber' => '+63 919 337 1102', 'ext' => '2233', 'office' => 'bgc', 'status' => 'active', 'hired' => '2023-02-27', 'regularized' => '2023-05-27', 'skills' => ['Figma', 'Illustration', 'Motion Design'], 'about' => "Designs the visual language for Group-wide internal tools.", 'linkedin' => 'bianca-aquino', 'supervisor' => 'EMP-00021', 'emergency' => ['Ruth Aquino', 'Mother', '+63 919 555 1010']],
            ['code' => 'EMP-00044', 'first' => 'Marco', 'middle' => 'Villaruel', 'last' => 'Dela Peña', 'nickname' => null, 'gender' => 'Male', 'birthday' => '02-08', 'desig' => 305, 'dept' => 202, 'company' => 102, 'mobile' => '+63 917 664 5591', 'viber' => '+63 917 664 5591', 'ext' => '2245', 'office' => 'bgc', 'status' => 'active', 'hired' => '2021-07-19', 'regularized' => '2021-10-19', 'skills' => ['PHP', 'Laravel', 'Redis', 'AWS'], 'about' => "Owns the sync services that keep every Group system talking to each other.", 'linkedin' => 'marco-delapena', 'supervisor' => null, 'emergency' => ['Teresa Dela Peña', 'Mother', '+63 917 555 1120']],
            ['code' => 'EMP-00007B', 'first' => 'Sofia', 'middle' => 'Reyes', 'last' => 'Mendoza', 'nickname' => null, 'gender' => 'Female', 'birthday' => '04-17', 'desig' => 310, 'dept' => 204, 'company' => 101, 'mobile' => '+63 918 226 7734', 'viber' => '+63 918 226 7734', 'ext' => '1106', 'office' => 'ma', 'status' => 'active', 'hired' => '2022-10-03', 'regularized' => '2023-01-03', 'skills' => ['Sourcing', 'Interviewing', 'Employer Branding'], 'about' => 'Sources and screens talent for corporate roles across the Group.', 'linkedin' => 'sofia-mendoza-hr', 'supervisor' => 'EMP-00012', 'emergency' => ['Carlo Mendoza', 'Spouse', '+63 918 555 1230']],
            ['code' => 'EMP-00066', 'first' => 'Julian', 'middle' => 'Torres', 'last' => 'Castillo', 'nickname' => null, 'gender' => 'Male', 'birthday' => '06-25', 'desig' => 314, 'dept' => 206, 'company' => 106, 'mobile' => '+63 917 552 8834', 'viber' => '+63 917 552 8834', 'ext' => '5501', 'office' => 'ceb', 'status' => 'active', 'hired' => '2016-11-11', 'regularized' => '2017-02-11', 'skills' => ['Channel Management', 'Forecasting', 'Leadership'], 'about' => 'Leads the Visayas–Mindanao retail sales team.', 'linkedin' => 'julian-castillo-sales', 'supervisor' => null, 'emergency' => ['Anna Castillo', 'Spouse', '+63 917 555 1340']],
            ['code' => 'EMP-00090', 'first' => 'Kristine', 'middle' => 'Alvarez', 'last' => 'Navarro', 'nickname' => null, 'gender' => 'Female', 'birthday' => '12-19', 'desig' => 316, 'dept' => 205, 'company' => 105, 'mobile' => '+63 917 330 9982', 'viber' => '+63 917 330 9982', 'ext' => '3301', 'office' => 'ma', 'status' => 'active', 'hired' => '2019-01-15', 'regularized' => '2019-04-15', 'skills' => ['GAAP', 'Tax Compliance', 'QuickBooks'], 'about' => "Manages the books for Cherry Financial Services and its lending arm.", 'linkedin' => 'kristine-navarro-cpa', 'supervisor' => null, 'emergency' => ['Oscar Navarro', 'Father', '+63 917 555 1450']],
            ['code' => 'EMP-00058', 'first' => 'Adrian', 'middle' => 'Cruz', 'last' => 'Gomez', 'nickname' => null, 'gender' => 'Male', 'birthday' => '03-03', 'desig' => 318, 'dept' => 210, 'company' => 104, 'mobile' => '+63 918 774 2210', 'viber' => '+63 918 774 2210', 'ext' => '6610', 'office' => 'lag', 'status' => 'on_leave', 'hired' => '2018-08-20', 'regularized' => '2018-11-20', 'skills' => ['Inventory Control', 'Logistics', 'Team Leadership'], 'about' => 'Runs inbound and outbound operations at the Sta. Rosa warehouse.', 'linkedin' => null, 'supervisor' => 'EMP-00003', 'emergency' => ['Nina Gomez', 'Spouse', '+63 918 555 1560']],
            ['code' => 'EMP-00081', 'first' => 'Ella', 'middle' => 'Marquez', 'last' => 'Tan', 'nickname' => null, 'gender' => 'Female', 'birthday' => '09-09', 'desig' => 308, 'dept' => 207, 'company' => 101, 'mobile' => '+63 919 221 3345', 'viber' => '+63 919 221 3345', 'ext' => '1141', 'office' => 'ma', 'status' => 'active', 'hired' => '2023-05-08', 'regularized' => '2023-08-08', 'skills' => ['Social Media', 'Copywriting', 'Adobe CC'], 'about' => 'Handles social content and brand campaigns for Cherry Mobile.', 'linkedin' => 'ella-tan', 'supervisor' => 'EMP-00007', 'emergency' => ["Ella's mother", 'Mother', '+63 919 555 1670']],
            ['code' => 'EMP-00037', 'first' => 'Gabriel', 'middle' => 'Serrano', 'last' => 'Rivera', 'nickname' => null, 'gender' => 'Male', 'birthday' => '08-15', 'desig' => 321, 'dept' => 211, 'company' => 103, 'mobile' => '+63 917 663 8820', 'viber' => '+63 917 663 8820', 'ext' => '4410', 'office' => 'bgc', 'status' => 'active', 'hired' => '2020-09-14', 'regularized' => '2020-12-14', 'skills' => ['Facilities Management', 'Vendor Relations', 'Leasing'], 'about' => 'Manages tenant relations and facilities across Cherry Realty properties.', 'linkedin' => 'gabriel-rivera-cre', 'supervisor' => null, 'emergency' => ['Diane Rivera', 'Spouse', '+63 917 555 1780']],
            ['code' => 'EMP-00099', 'first' => 'Renz', 'middle' => 'Aba', 'last' => 'Aguilar', 'nickname' => null, 'gender' => 'Male', 'birthday' => '01-30', 'desig' => 306, 'dept' => 202, 'company' => 102, 'mobile' => '+63 920 445 1189', 'viber' => '+63 920 445 1189', 'ext' => '2250', 'office' => 'bgc', 'status' => 'active', 'hired' => '2026-07-06', 'regularized' => null, 'skills' => ['JavaScript', 'Laravel', 'Git'], 'about' => "Recently joined the platform team, ramping up on the Group's internal tools.", 'linkedin' => null, 'supervisor' => 'EMP-00021', 'emergency' => ['Rina Aguilar', 'Mother', '+63 920 555 1890']],
            ['code' => 'EMP-00101', 'first' => 'Faith', 'middle' => 'Olivar', 'last' => 'Delgado', 'nickname' => null, 'gender' => 'Female', 'birthday' => '11-22', 'desig' => 312, 'dept' => 208, 'company' => 101, 'mobile' => '+63 918 221 7765', 'viber' => '+63 918 221 7765', 'ext' => '1189', 'office' => 'ma', 'status' => 'active', 'hired' => '2026-07-15', 'regularized' => null, 'skills' => ['Customer Support', 'Communication'], 'about' => 'New addition to the CX floor team, handling frontline inquiries.', 'linkedin' => null, 'supervisor' => 'EMP-00019', 'emergency' => ['Josie Delgado', 'Mother', '+63 918 555 1990']],
            ['code' => 'EMP-00102', 'first' => 'Karlo', 'middle' => 'Fajardo', 'last' => 'Manalo', 'nickname' => null, 'gender' => 'Male', 'birthday' => '05-05', 'desig' => 319, 'dept' => 210, 'company' => 104, 'mobile' => '+63 919 664 2210', 'viber' => '+63 919 664 2210', 'ext' => '6615', 'office' => 'lag', 'status' => 'active', 'hired' => '2026-06-28', 'regularized' => null, 'skills' => ['Inventory', 'Forklift Operation'], 'about' => 'Supports receiving and dispatch at the Sta. Rosa hub.', 'linkedin' => null, 'supervisor' => 'EMP-00003', 'emergency' => ['Bea Manalo', 'Spouse', '+63 919 555 2010']],
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
                    'email' => $emailFor($data['first'], $data['last'], $data['company']),
                    'company_id' => $companies[$data['company']],
                    'department_id' => $departments[$data['dept']],
                    'designation_id' => $designations[$data['desig']],
                    'employment_status' => $data['status'],
                    'date_hired' => $data['hired'],
                    'date_regularized' => $data['regularized'],
                    'job_level' => null,
                    'source' => EmployeeSource::HrSync,
                    'last_synced_at' => now(),
                ],
            );

            $employee->profile()->updateOrCreate(['employee_id' => $employee->id], [
                'nickname' => $data['nickname'],
                'gender' => $data['gender'],
                'birthday' => '2000-'.$data['birthday'],
                'personal_email' => strtolower($data['first']).'.'.strtolower(str_replace(' ', '', $data['last'])).'@gmail.com',
                'mobile_number' => $data['mobile'],
                'viber_number' => $data['viber'],
                'local_extension' => $data['ext'],
                'office_location_id' => $offices[$officeNames[$data['office']]],
                'about_me' => $data['about'],
                'linkedin_url' => $data['linkedin'] ? "linkedin.com/in/{$data['linkedin']}" : null,
                'emergency_contact_name' => $data['emergency'][0],
                'emergency_contact_relationship' => $data['emergency'][1],
                'emergency_contact_phone' => $data['emergency'][2],
            ]);

            $skillIds = collect($data['skills'])->map(fn (string $name) => Skill::firstOrCreate(['name' => $name])->id);
            $employee->skills()->sync($skillIds);
        }

        // Second pass: resolve supervisors and department heads, now that every employee_id exists.
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

        $departmentHeads = [
            201 => 'EMP-00021', 202 => 'EMP-00044', 203 => 'EMP-00034', 204 => 'EMP-00012',
            205 => 'EMP-00090', 206 => 'EMP-00066', 207 => 'EMP-00007', 208 => 'EMP-00019',
            209 => 'EMP-00028', 210 => 'EMP-00003', 211 => 'EMP-00037',
        ];

        foreach ($departmentHeads as $deptHrRef => $headCode) {
            $head = Employee::where('employee_id', $headCode)->first();
            if ($head) {
                Department::where('hr_ref_id', $deptHrRef)->update(['department_head_id' => $head->id]);
            }
        }
    }
}
