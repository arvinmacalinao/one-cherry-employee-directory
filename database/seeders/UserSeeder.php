<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Employee::where('employee_id', 'EMP-00034')->first(); // Miguel Torres, IT Support
        $employee = Employee::where('employee_id', 'EMP-00021')->first(); // Andrea Reyes, Senior UX Designer

        $adminUser = User::updateOrCreate(
            ['email' => $admin->email],
            ['name' => $admin->full_name, 'password' => 'password', 'employee_id' => $admin->id, 'is_active' => true],
        );
        $adminUser->syncRoles(['Administrator']);

        $employeeUser = User::updateOrCreate(
            ['email' => $employee->email],
            ['name' => $employee->full_name, 'password' => 'password', 'employee_id' => $employee->id, 'is_active' => true],
        );
        $employeeUser->syncRoles(['Employee']);

        $this->command?->table(
            ['Role', 'Email', 'Password'],
            [
                ['Administrator', $adminUser->email, 'password'],
                ['Employee', $employeeUser->email, 'password'],
            ],
        );
    }
}
