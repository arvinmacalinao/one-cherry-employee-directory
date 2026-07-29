<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['hr_ref_id' => 101, 'name' => 'Cherry Mobile Communications', 'address' => '21F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1000', 'email' => 'info@cherrymobile.onecherry.group', 'website' => 'cherrymobile.onecherry.group'],
            ['hr_ref_id' => 102, 'name' => 'Cherry Digital Solutions', 'address' => '12F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1200', 'email' => 'hello@cherrydigital.onecherry.group', 'website' => 'cherrydigital.onecherry.group'],
            ['hr_ref_id' => 103, 'name' => 'Cherry Realty & Development', 'address' => '8F Cherry Realty Center, BGC, Taguig', 'phone' => '+63 2 8888 1300', 'email' => 'info@cherryrealty.onecherry.group', 'website' => 'cherryrealty.onecherry.group'],
            ['hr_ref_id' => 104, 'name' => 'Cherry Logistics & Fulfillment', 'address' => 'Cherry Logistics Hub, Sta. Rosa, Laguna', 'phone' => '+63 49 511 2200', 'email' => 'ops@cherrylogistics.onecherry.group', 'website' => 'cherrylogistics.onecherry.group'],
            ['hr_ref_id' => 105, 'name' => 'Cherry Financial Services', 'address' => '30F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1400', 'email' => 'info@cherryfinancial.onecherry.group', 'website' => 'cherryfinancial.onecherry.group'],
            ['hr_ref_id' => 106, 'name' => 'Cherry Retail Group', 'address' => 'Cherry Retail Plaza, Cebu Business Park, Cebu City', 'phone' => '+63 32 888 1500', 'email' => 'info@cherryretail.onecherry.group', 'website' => 'cherryretail.onecherry.group'],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(
                ['hr_ref_id' => $company['hr_ref_id']],
                [...$company, 'slug' => Str::slug($company['name']), 'is_active' => true],
            );
        }
    }
}
