<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    protected array $offices = [
        ['key' => 'ma', 'name' => 'Makati HQ', 'address' => 'Makati City, Philippines', 'company' => null],
        ['key' => 'bgc', 'name' => 'BGC Tower', 'address' => 'Taguig City, Philippines', 'company' => 102],
        ['key' => 'ceb', 'name' => 'Cebu Business Park', 'address' => 'Cebu City, Philippines', 'company' => 106],
        ['key' => 'lag', 'name' => 'Sta. Rosa Hub', 'address' => 'Sta. Rosa, Laguna, Philippines', 'company' => 104],
        ['key' => 'dav', 'name' => 'Davao Branch', 'address' => 'Davao City, Philippines', 'company' => null],
        ['key' => 'clk', 'name' => 'Clark Office', 'address' => 'Clark Freeport, Pampanga, Philippines', 'company' => null, 'active' => false],
    ];

    public function run(): void
    {
        $companiesByHrRef = Company::pluck('id', 'hr_ref_id');

        foreach ($this->offices as $office) {
            OfficeLocation::updateOrCreate(
                ['name' => $office['name']],
                [
                    'company_id' => $office['company'] ? $companiesByHrRef[$office['company']] : null,
                    'address' => $office['address'],
                    'is_active' => $office['active'] ?? true,
                ],
            );
        }
    }

    public function keyMap(): array
    {
        return OfficeLocation::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [
                collect($this->offices)->firstWhere('name', $name)['key'] => $id,
            ])->all();
    }
}
