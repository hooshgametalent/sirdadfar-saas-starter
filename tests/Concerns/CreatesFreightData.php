<?php

namespace Tests\Concerns;

use App\Contracts\SmsSender;
use App\Models\City;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\VehicleType;
use Tests\Fakes\FakeSmsSender;

trait CreatesFreightData
{
    protected function fakeSms(): FakeSmsSender
    {
        $fake = new FakeSmsSender();

        $this->app->instance(SmsSender::class, $fake);

        return $fake;
    }

    protected function approvedDriver(): User
    {
        return DriverProfile::factory()->approved()->create()->user;
    }

    protected function pendingDriver(): User
    {
        return DriverProfile::factory()->create()->user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function orderData(array $overrides = []): array
    {
        return array_merge([
            'origin_city_id' => City::factory()->create()->getKey(),
            'destination_city_id' => City::factory()->create()->getKey(),
            'origin_address' => 'نشانی نمونه مبدأ',
            'destination_address' => 'نشانی نمونه مقصد',
            'cargo_type' => 'مصالح ساختمانی',
            'weight_kg' => 8000,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'vehicle_type_id' => VehicleType::factory()->create()->getKey(),
            'special_requirements' => ['loading_help' => true],
            'offered_price' => 28500000,
        ], $overrides);
    }
}
