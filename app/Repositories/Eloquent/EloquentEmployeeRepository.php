<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    protected function directoryEagerLoads(): array
    {
        return ['company', 'department', 'designation', 'profile.officeLocation'];
    }

    public function find(int $id): ?Employee
    {
        return Employee::with($this->directoryEagerLoads())->find($id);
    }

    public function findByEmployeeCode(string $employeeId): ?Employee
    {
        return Employee::where('employee_id', $employeeId)->first();
    }

    public function paginateForDirectory(array $filters, string $sort, int $perPage = 24): LengthAwarePaginator
    {
        $query = Employee::query()
            ->with($this->directoryEagerLoads())
            ->visibleInDirectory();

        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('profile', fn (Builder $p) => $p->where('mobile_number', 'like', "%{$term}%"))
                    ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['designation_id'])) {
            $query->where('designation_id', $filters['designation_id']);
        }

        if (! empty($filters['office_location_id'])) {
            $query->whereHas('profile', fn (Builder $p) => $p->where('office_location_id', $filters['office_location_id']));
        }

        if (! empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (! empty($filters['letter'])) {
            $query->where('last_name', 'like', $filters['letter'].'%');
        }
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name' => $query->orderBy('last_name')->orderBy('first_name'),
            'department' => $query->join('departments', 'departments.id', '=', 'employees.department_id')
                ->orderBy('departments.name')
                ->select('employees.*'),
            'company' => $query->join('companies', 'companies.id', '=', 'employees.company_id')
                ->orderBy('companies.name')
                ->select('employees.*'),
            default => $query->orderByDesc('date_hired'),
        };
    }

    public function search(string $term, int $limit = 8): Collection
    {
        return Employee::query()
            ->with(['company', 'department', 'designation'])
            ->visibleInDirectory()
            ->search($term)
            ->limit($limit)
            ->get();
    }

    public function newHires(int $days = 30): Collection
    {
        return Employee::query()
            ->with(['company', 'department'])
            ->visibleInDirectory()
            ->where('date_hired', '>=', Carbon::now()->subDays($days))
            ->orderByDesc('date_hired')
            ->get();
    }

    public function birthdaysUpcoming(int $limit = 6): Collection
    {
        // MySQL: order by "days until next birthday", wrapping the year.
        return Employee::query()
            ->with(['company', 'department', 'profile'])
            ->visibleInDirectory()
            ->whereHas('profile', fn (Builder $p) => $p->whereNotNull('birthday'))
            ->join('employee_profiles', 'employee_profiles.employee_id', '=', 'employees.id')
            ->selectRaw('employees.*, MOD(DATEDIFF(
                DATE_ADD(employee_profiles.birthday, INTERVAL YEAR(CURDATE()) - YEAR(employee_profiles.birthday) YEAR),
                CURDATE()
            ) + 366, 366) as days_until_birthday')
            ->orderBy('days_until_birthday')
            ->limit($limit)
            ->get();
    }

    public function countVisible(): int
    {
        return Employee::visibleInDirectory()->count();
    }

    public function create(array $attributes): Employee
    {
        return Employee::create($attributes);
    }

    public function update(Employee $employee, array $attributes): Employee
    {
        $employee->update($attributes);

        return $employee->refresh();
    }

    public function delete(Employee $employee): bool
    {
        return (bool) $employee->delete();
    }
}
