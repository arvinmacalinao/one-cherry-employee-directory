<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Round-trip bulk editor for the fields an Admin is actually allowed to touch —
 * export gives a CSV an Admin can fill in offline (most commonly: email and
 * telephone/local extension, since HR frequently doesn't provide email at all —
 * see architecture-plan.md §2.5), import applies it back.
 *
 * Deliberately narrow: this can only update existing employees (matched by the
 * immutable employee_id HR sync key) and only ever touches the same fields the
 * single-employee edit form allows — email (fallback-editable) plus the
 * directory-owned profile fields. It can never create an employee or touch any
 * HR-owned column, even if a HR-owned column's text happens to be present in
 * the file — those columns are exported for the Admin's own reference only and
 * are never read back on import.
 */
class EmployeeCsvService
{
    public const HEADERS = [
        'employee_id', 'first_name', 'last_name', 'company', 'department', 'designation',
        'email', 'viber_number', 'telephone', 'local_extension', 'office_location', 'birthday', 'about_me',
    ];

    /** Columns from HEADERS that import actually reads. Everything else is reference-only. */
    protected const EDITABLE_COLUMNS = [
        'email', 'viber_number', 'telephone', 'local_extension', 'office_location', 'birthday', 'about_me',
    ];

    public function __construct(
        protected EmployeeProfileService $profileService,
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function exportRows(array $filters = []): \Illuminate\Support\Collection
    {
        $query = Employee::with(['company', 'department', 'designation', 'profile.officeLocation'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('employee_id', 'like', "%{$term}%"));
        }

        return $query->get()->map(fn (Employee $employee) => [
            'employee_id' => $employee->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'company' => $employee->company?->name,
            'department' => $employee->department?->name,
            'designation' => $employee->designation?->name,
            'email' => $employee->email,
            'viber_number' => $employee->profile?->viber_number,
            'telephone' => $employee->profile?->telephone,
            'local_extension' => $employee->profile?->local_extension,
            'office_location' => $employee->profile?->officeLocation?->name,
            'birthday' => $employee->profile?->birthday?->format('Y-m-d'),
            'about_me' => $employee->profile?->about_me,
        ]);
    }

    public function import(UploadedFile $file, ?User $admin = null): EmployeeImportResult
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return new EmployeeImportResult(warnings: ['Could not open the uploaded file.']);
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return new EmployeeImportResult(warnings: ['The file is empty.']);
        }

        $columns = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $employeeIdIndex = array_search('employee_id', $columns, true);

        if ($employeeIdIndex === false) {
            fclose($handle);

            return new EmployeeImportResult(warnings: ['No "employee_id" column found — this must be the same file the Export button produced, with that column intact.']);
        }

        $officeLocationsByName = OfficeLocation::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);

        $rowsProcessed = 0;
        $employeesUpdated = 0;
        $warnings = [];
        $rowNumber = 1; // header was row 1

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank rows silently
            }

            $rowsProcessed++;
            $data = [];
            foreach ($columns as $i => $column) {
                $data[$column] = trim((string) ($row[$i] ?? ''));
            }

            $employeeId = $data['employee_id'] ?? '';

            if ($employeeId === '') {
                $warnings[] = "Row {$rowNumber}: blank employee_id, skipped.";

                continue;
            }

            $employee = Employee::where('employee_id', $employeeId)->first();

            if (! $employee) {
                $warnings[] = "Row {$rowNumber}: employee_id \"{$employeeId}\" not found — this import can only update existing employees, never create one.";

                continue;
            }

            $rowWarnings = [];
            $changed = false;

            // Email lives on `employees`, is fallback-editable (see architecture-plan.md
            // §2.5) — blank cell means "don't touch", not "clear it".
            if (($data['email'] ?? '') !== '') {
                $validator = Validator::make(
                    ['email' => $data['email']],
                    ['email' => ['email', 'max:255', 'unique:employees,email,'.$employee->id]],
                );

                if ($validator->fails()) {
                    $rowWarnings[] = "email \"{$data['email']}\" ".implode(' ', $validator->errors()->get('email'));
                } elseif ($employee->email !== $data['email']) {
                    $employee->update(['email' => $data['email']]);
                    $changed = true;
                }
            }

            $profileAttributes = [];

            foreach (['viber_number', 'telephone', 'local_extension', 'about_me'] as $field) {
                if (($data[$field] ?? '') !== '') {
                    $profileAttributes[$field] = $data[$field];
                }
            }

            if (($data['office_location'] ?? '') !== '') {
                $officeId = $officeLocationsByName->get(strtolower($data['office_location']));

                if ($officeId) {
                    $profileAttributes['office_location_id'] = $officeId;
                } else {
                    $rowWarnings[] = "office location \"{$data['office_location']}\" not found — left unchanged.";
                }
            }

            if (($data['birthday'] ?? '') !== '') {
                try {
                    $profileAttributes['birthday'] = Carbon::parse($data['birthday'])->format('Y-m-d');
                } catch (\Throwable) {
                    $rowWarnings[] = "birthday \"{$data['birthday']}\" could not be parsed as a date — left unchanged.";
                }
            }

            if (! empty($profileAttributes)) {
                $this->profileService->updateProfile($employee, $profileAttributes);
                $changed = true;
            }

            if ($changed) {
                $employeesUpdated++;
            }

            foreach ($rowWarnings as $warning) {
                $warnings[] = "Row {$rowNumber} ({$employeeId}): {$warning}";
            }
        }

        fclose($handle);

        return new EmployeeImportResult(
            rowsProcessed: $rowsProcessed,
            employeesUpdated: $employeesUpdated,
            warnings: $warnings,
        );
    }
}
