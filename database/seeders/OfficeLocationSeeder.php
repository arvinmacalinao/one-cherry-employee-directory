<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    protected array $offices = [
        ['key' => 'ma', 'name' => 'Makati HQ', 'city' => 'Makati City', 'phone' => '+63 2 8888 1000', 'company' => null],
        ['key' => 'bgc', 'name' => 'BGC Tower', 'city' => 'Taguig City', 'phone' => '+63 2 8888 1200', 'company' => 102],
        ['key' => 'ceb', 'name' => 'Cebu Business Park', 'city' => 'Cebu City', 'phone' => '+63 32 888 1500', 'company' => 106],
        ['key' => 'lag', 'name' => 'Sta. Rosa Hub', 'city' => 'Sta. Rosa, Laguna', 'phone' => '+63 49 511 2200', 'company' => 104],
        ['key' => 'dav', 'name' => 'Davao Branch', 'city' => 'Davao City', 'phone' => '+63 82 888 1600', 'company' => null],
        ['key' => 'clk', 'name' => 'Clark Office', 'city' => 'Clark Freeport, Pampanga', 'phone' => '+63 45 888 1700', 'company' => null, 'active' => false],
    ];

    public function run(): void
    {
        $companiesByHrRef = Company::pluck('id', 'hr_ref_id');

        foreach ($this->offices as $office) {
            OfficeLocation::updateOrCreate(
                ['name' => $office['name']],
                [
                    'company_id' => $office['company'] ? $companiesByHrRef[$office['company']] : null,
                    'city' => $office['city'],
                    'country' => 'Philippines',
                    'phone' => $office['phone'],
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
