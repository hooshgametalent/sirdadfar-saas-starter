<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled because the order observer assigns
     * references.
     */
    public function run(): void
    {
        $this->call([
            ProvinceCitySeeder::class,
            VehicleTypeSeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
