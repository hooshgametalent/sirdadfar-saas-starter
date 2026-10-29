<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Vehicle types keyed by slug, in display order. Capacity is a typical
     * maximum load in kilograms.
     *
     * @var array<string, array{name: string, capacity_kg: int}>
     */
    public const TYPES = [
        'vanet' => ['name' => 'وانت‌بار', 'capacity_kg' => 1500],
        'nissan' => ['name' => 'نیسان', 'capacity_kg' => 2000],
        'khavar' => ['name' => 'خاور', 'capacity_kg' => 5000],
        'single-truck' => ['name' => 'کامیون تک', 'capacity_kg' => 10000],
        'tandem-truck' => ['name' => 'کامیون جفت', 'capacity_kg' => 15000],
        'ten-wheel-truck' => ['name' => 'کامیون ۱۰ چرخ', 'capacity_kg' => 18000],
        'flatbed-trailer' => ['name' => 'تریلی کفی', 'capacity_kg' => 25000],
        'curtain-trailer' => ['name' => 'تریلی چادری', 'capacity_kg' => 25000],
        'refrigerated' => ['name' => 'یخچالی', 'capacity_kg' => 12000],
        'bunker' => ['name' => 'بونکر', 'capacity_kg' => 25000],
        'dump-truck' => ['name' => 'کمپرسی', 'capacity_kg' => 15000],
    ];

    public function run(): void
    {
        $order = 1;

        foreach (self::TYPES as $slug => $type) {
            VehicleType::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $type['name'],
                    'capacity_kg' => $type['capacity_kg'],
                    'is_active' => true,
                    'sort_order' => $order++,
                ],
            );
        }
    }
}
