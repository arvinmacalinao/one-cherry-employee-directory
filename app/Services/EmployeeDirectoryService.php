<?php

namespace App\Services;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeeDirectoryService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employees,
        protected CompanyRepositoryInterface $companies,
        protected DepartmentRepositoryInterface $departments,
        protected OfficeLocationRepositoryInterface $officeLocations,
    ) {}

    public function browse(array $filters, string $sort = 'newest', int $perPage = 24): LengthAwarePaginator
    {
        return $this->employees->paginateForDirectory($filters, $sort, $perPage);
    }

    public function newHires(int $days = 30): Collection
    {
        return $this->employees->newHires($days);
    }

    public function birthdayCelebrants(int $limit = 6): Collection
    {
        return $this->employees->birthdaysUpcoming($limit);
    }
}
