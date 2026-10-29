<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\City;
use App\Models\Order;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates orders directly in a given status for tests. Real status changes
 * always go through App\Services\OrderLifecycle.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipper_id' => User::factory()->shipper(),
            'origin_city_id' => City::factory(),
            'destination_city_id' => City::factory(),
            'origin_address' => 'خیابان نمونه، پلاک ۱',
            'destination_address' => 'میدان نمونه، انبار ۲',
            'cargo_type' => fake()->randomElement(['مصالح ساختمانی', 'لوازم خانگی', 'مواد غذایی', 'کالای تجاری']),
            'weight_kg' => fake()->numberBetween(500, 20000),
            'volume_m3' => null,
            'pickup_date' => now()->addDays(3)->toDateString(),
            'vehicle_type_id' => VehicleType::factory(),
            'special_requirements' => null,
            'offered_price' => fake()->numberBetween(20, 700) * 100000,
            'notes' => null,
            'status' => OrderStatus::PendingReview,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function openForOffers(): static
    {
        return $this->status(OrderStatus::OpenForOffers);
    }

    public function assignedTo(User $driver, ?int $price = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Assigned,
            'assigned_driver_id' => $driver->getKey(),
            'final_price' => $price ?? $attributes['offered_price'],
        ]);
    }

    public function delivered(?User $driver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Delivered,
            'assigned_driver_id' => $driver?->getKey() ?? User::factory()->driver(),
            'final_price' => $attributes['offered_price'],
        ]);
    }
}
