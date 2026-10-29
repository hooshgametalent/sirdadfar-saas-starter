<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->delivered(),
            'shipper_id' => fn (array $attributes) => Order::find($attributes['order_id'])->shipper_id,
            'driver_id' => fn (array $attributes) => Order::find($attributes['order_id'])->assigned_driver_id,
            'rating' => fake()->numberBetween(3, 5),
            'body' => 'بار سالم و به موقع تحویل شد.',
            'publish_consent' => true,
            'display_name' => 'مشتری بارراه',
            'status' => ReviewStatus::Pending,
            'is_featured' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Approved,
            'moderated_at' => now(),
        ]);
    }

    public function withoutConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_consent' => false,
        ]);
    }
}
