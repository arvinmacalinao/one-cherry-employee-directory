<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

/**
 * Read-only for everyone — Internal Users view, Administrators edit directory-owned
 * fields from /admin/employees instead. See architecture-plan.md §2.4, §6.3.
 */
class EmployeeProfile extends Component
{
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $employee->load(['company', 'department', 'designation', 'status', 'profile.officeLocation']);
        $this->employee = $employee;
    }

    public function render()
    {
        return view('livewire.employee-profile')->layout('layouts.app', ['header' => 'Employee Profile']);
    }
}
