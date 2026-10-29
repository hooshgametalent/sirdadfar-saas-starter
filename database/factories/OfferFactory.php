<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->openForOffers(),
            'driver_id' => User::factory()->driver(),
            'price' => fake()->numberBetween(20, 700) * 100000,
            'message' => null,
            'status' => OfferStatus::Pending,
        ];
    }
}
