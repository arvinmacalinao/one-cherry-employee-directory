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
            ['hr_ref_id' => 101, 'name' => 'Cherry Mobile Communications', 'color_theme' => '#790002', 'address' => '21F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1000', 'email' => 'info@cherrymobile.onecherry.group', 'website' => 'cherrymobile.onecherry.group', 'description' => 'Handset retail, telco partnerships and after-sales service.'],
            ['hr_ref_id' => 102, 'name' => 'Cherry Digital Solutions', 'color_theme' => '#2F5D8A', 'address' => '12F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1200', 'email' => 'hello@cherrydigital.onecherry.group', 'website' => 'cherrydigital.onecherry.group', 'description' => 'Builds and maintains technology platforms across the Group.'],
            ['hr_ref_id' => 103, 'name' => 'Cherry Realty & Development', 'color_theme' => '#6B7A3F', 'address' => '8F Cherry Realty Center, BGC, Taguig', 'phone' => '+63 2 8888 1300', 'email' => 'info@cherryrealty.onecherry.group', 'website' => 'cherryrealty.onecherry.group', 'description' => 'Develops and manages commercial and residential properties.'],
            ['hr_ref_id' => 104, 'name' => 'Cherry Logistics & Fulfillment', 'color_theme' => '#B8722A', 'address' => 'Cherry Logistics Hub, Sta. Rosa, Laguna', 'phone' => '+63 49 511 2200', 'email' => 'ops@cherrylogistics.onecherry.group', 'website' => 'cherrylogistics.onecherry.group', 'description' => 'Warehousing, fleet and last-mile fulfillment for the Group.'],
            ['hr_ref_id' => 105, 'name' => 'Cherry Financial Services', 'color_theme' => '#4A4F6B', 'address' => '30F Cherry Tower, Ayala Ave, Makati City', 'phone' => '+63 2 8888 1400', 'email' => 'info@cherryfinancial.onecherry.group', 'website' => 'cherryfinancial.onecherry.group', 'description' => 'Lending, insurance and treasury services.'],
            ['hr_ref_id' => 106, 'name' => 'Cherry Retail Group', 'color_theme' => '#8A4B6B', 'address' => 'Cherry Retail Plaza, Cebu Business Park, Cebu City', 'phone' => '+63 32 888 1500', 'email' => 'info@cherryretail.onecherry.group', 'website' => 'cherryretail.onecherry.group', 'description' => "Operates the Group's retail storefronts and regional sales network."],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(
                ['hr_ref_id' => $company['hr_ref_id']],
                [...$company, 'slug' => Str::slug($company['name']), 'is_active' => true],
            );
        }
    }
}
