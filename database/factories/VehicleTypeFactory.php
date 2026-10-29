<?php

namespace Database\Factories;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'name' => 'خودرو نمونه '.$number,
            'slug' => 'vehicle-'.$number,
            'capacity_kg' => fake()->randomElement([1500, 3500, 6000, 10000, 24000]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
