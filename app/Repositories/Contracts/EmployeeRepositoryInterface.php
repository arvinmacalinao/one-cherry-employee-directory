<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public function find(int $id): ?Employee;

    public function findByEmployeeCode(string $employeeId): ?Employee;

    /**
     * @param  array{search?: string, company_id?: int, department_id?: int, designation_id?: int,
     *               office_location_id?: int, employee_status_id?: int, letter?: string}  $filters
     */
    public function paginateForDirectory(array $filters, string $sort, int $perPage = 24): LengthAwarePaginator;

    public function search(string $term, int $limit = 8): Collection;

    public function newHires(int $days = 30): Collection;

    public function birthdaysUpcoming(int $limit = 6): Collection;

    public function countVisible(): int;

    public function create(array $attributes): Employee;

    public function update(Employee $employee, array $attributes): Employee;

    public function delete(Employee $employee): bool;
}
