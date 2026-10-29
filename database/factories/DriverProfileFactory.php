<?php

namespace Database\Factories;

use App\Enums\DriverVerificationStatus;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'national_code' => null,
            'vehicle_type_id' => VehicleType::factory(),
            'plate_number' => fake()->numerify('##ع###-##'),
            'vehicle_model' => fake()->randomElement(['بنز ۱۹۲۴', 'ولوو FH', 'ایسوزو NPR', 'نیسان زامیاد']),
            'capacity_kg' => fake()->randomElement([2000, 6000, 10000, 24000]),
            'home_city_id' => null,
            'verification_status' => DriverVerificationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => DriverVerificationStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => DriverVerificationStatus::Rejected,
            'rejection_reason' => 'مدارک ناخوانا است.',
        ]);
    }
}
