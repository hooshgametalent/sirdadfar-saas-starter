<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Faq;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use App\Models\VehicleType;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ProvinceCitySeeder;
use Database\Seeders\VehicleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_type_seeder_creates_exactly_the_requested_types(): void
    {
        $this->seed(VehicleTypeSeeder::class);
        $this->seed(VehicleTypeSeeder::class);

        $this->assertSame(11, VehicleType::query()->count());
        $this->assertSame([
            'وانت‌بار',
            'نیسان',
            'خاور',
            'کامیون تک',
            'کامیون جفت',
            'کامیون ۱۰ چرخ',
            'تریلی کفی',
            'تریلی چادری',
            'یخچالی',
            'بونکر',
            'کمپرسی',
        ], VehicleType::query()->orderBy('sort_order')->pluck('name')->all());
    }

    public function test_province_seeder_creates_all_31_provinces_with_cities(): void
    {
        $this->seed(ProvinceCitySeeder::class);
        $this->seed(ProvinceCitySeeder::class);

        $this->assertSame(31, Province::query()->count());
        $this->assertSame(150, City::query()->count());
        $this->assertSame(0, Province::query()->doesntHave('cities')->count());
        $this->assertTrue(City::query()->where('slug', 'bandar-abbas')->where('name', 'بندرعباس')->exists());
    }

    public function test_admin_seeder_skips_without_configured_credentials(): void
    {
        config(['freight.admin.mobile' => null, 'freight.admin.password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::query()->count());
    }

    public function test_admin_seeder_creates_admin_from_configuration(): void
    {
        config(['freight.admin.mobile' => '09120000000', 'freight.admin.password' => 'a-strong-test-password']);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('mobile', '09120000000')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('a-strong-test-password', $admin->password));
    }

    public function test_database_seeder_builds_demo_data_through_the_lifecycle(): void
    {
        $this->seed();

        $this->assertSame(11, VehicleType::query()->count());
        $this->assertSame(31, Province::query()->count());
        $this->assertSame(5, Order::query()->count());

        foreach (Order::all() as $order) {
            $this->assertMatchesRegularExpression('/^BAR-\d{6}$/', $order->reference);
            $this->assertGreaterThan(0, OrderStatusHistory::query()->where('order_id', $order->id)->count());
        }

        $statuses = Order::query()->pluck('status')->map(fn (OrderStatus $status) => $status->value)->sort()->values()->all();
        $this->assertSame(['assigned', 'delivered', 'in_transit', 'open_for_offers', 'pending_review'], $statuses);

        $this->assertSame(1, Review::query()->publishable()->count());
        $this->assertSame(0, User::query()->where('role', UserRole::Admin->value)->count());

        foreach (User::all() as $user) {
            $this->assertMatchesRegularExpression('/^0912000000\d$/', $user->mobile);
        }

        $this->assertDatabaseHas('pages', ['slug' => 'tehran-to-isfahan', 'type' => 'route']);
        $this->assertDatabaseHas('articles', ['slug' => 'tehran-isfahan-vehicle-guide', 'status' => 'published']);
        $this->assertGreaterThanOrEqual(4, Faq::query()->count());
    }
}
