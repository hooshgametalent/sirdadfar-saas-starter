<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceCitySeeder extends Seeder
{
    /**
     * Seed all Iranian provinces and their main cities. Safe to run again.
     */
    public function run(): void
    {
        $provinces = require database_path('data/provinces.php');

        foreach ($provinces as $data) {
            $province = Province::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name']],
            );

            foreach ($data['cities'] as $slug => $name) {
                City::updateOrCreate(
                    ['slug' => $slug],
                    ['province_id' => $province->getKey(), 'name' => $name],
                );
            }
        }
    }
}
