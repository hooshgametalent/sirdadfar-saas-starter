<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'province_id' => Province::factory(),
            'name' => 'شهر نمونه '.$number,
            'slug' => 'city-'.$number,
        ];
    }
}
