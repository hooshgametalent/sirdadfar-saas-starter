<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_and_register_pages_render(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('ورود به حساب');
        $this->get(route('register'))->assertOk()->assertSee('نوع حساب');
    }

    public function test_shipper_can_register_with_persian_digits(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'شرکت نمونه')
            ->set('mobile', '۰۹۱۲۰۰۰۰۰۱۱')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->set('role', 'shipper')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('shipper.dashboard'));

        $user = User::query()->where('mobile', '09120000011')->firstOrFail();

        $this->assertSame(UserRole::Shipper, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
    }

    public function test_driver_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'راننده نمونه')
            ->set('mobile', '09120000012')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->set('role', 'driver')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('driver.dashboard'));

        $this->assertSame(UserRole::Driver, User::query()->where('mobile', '09120000012')->firstOrFail()->role);
    }

    public function test_staff_roles_cannot_be_self_assigned(): void
    {
        foreach (['admin', 'operator', 'content_editor'] as $role) {
            Livewire::test(Register::class)
                ->set('name', 'کاربر نمونه')
                ->set('mobile', '09120000013')
                ->set('password', 'secret-password')
                ->set('password_confirmation', 'secret-password')
                ->set('role', $role)
                ->call('register')
                ->assertHasErrors(['role' => 'in']);
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_registration_validates_mobile_and_uniqueness(): void
    {
        User::factory()->create(['mobile' => '09120000014']);

        Livewire::test(Register::class)
            ->set('name', 'کاربر نمونه')
            ->set('mobile', '12345')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors(['mobile' => 'regex']);

        Livewire::test(Register::class)
            ->set('name', 'کاربر نمونه')
            ->set('mobile', '09120000014')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors(['mobile' => 'unique']);
    }

    public function test_user_can_login_with_mobile_and_password(): void
    {
        $user = User::factory()->shipper()->create(['mobile' => '09001112233', 'password' => 'secret-password']);

        Livewire::test(Login::class)
            ->set('mobile', '۰۹۰۰ ۱۱۱ ۲۲۳۳')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('shipper.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_driver_and_staff_are_redirected_to_their_panels(): void
    {
        User::factory()->driver()->create(['mobile' => '09001112234', 'password' => 'secret-password']);
        User::factory()->operator()->create(['mobile' => '09001112235', 'password' => 'secret-password']);

        Livewire::test(Login::class)
            ->set('mobile', '09001112234')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertRedirect(route('driver.dashboard'));

        auth()->logout();

        Livewire::test(Login::class)
            ->set('mobile', '09001112235')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertRedirect(url('/admin'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['mobile' => '09001112233', 'password' => 'secret-password']);

        Livewire::test(Login::class)
            ->set('mobile', '09001112233')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('mobile')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['mobile' => '09001112233', 'password' => 'secret-password']);

        for ($attempt = 0; $attempt < Login::MAX_ATTEMPTS; $attempt++) {
            Livewire::test(Login::class)
                ->set('mobile', '09001112233')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors('mobile');
        }

        $component = Livewire::test(Login::class)
            ->set('mobile', '09001112233')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertHasErrors('mobile')
            ->assertNoRedirect();

        $this->assertStringContainsString('ثانیه دیگر', $component->errors()->first('mobile'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create(['mobile' => '09001112233', 'password' => 'secret-password']);

        $component = Livewire::test(Login::class)
            ->set('mobile', '09001112233')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertHasErrors('mobile')
            ->assertNoRedirect();

        $this->assertStringContainsString('غیرفعال', $component->errors()->first('mobile'));
        $this->assertGuest();
    }

    public function test_inactive_user_is_signed_out_of_the_panel(): void
    {
        $user = User::factory()->shipper()->inactive()->create();

        $this->actingAs($user)
            ->get(route('shipper.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_panels_are_restricted_by_role(): void
    {
        $this->get(route('shipper.dashboard'))->assertRedirect(route('login'));
        $this->get(route('driver.dashboard'))->assertRedirect(route('login'));

        $shipper = User::factory()->shipper()->create();
        $driver = User::factory()->driver()->create();

        $this->actingAs($shipper)->get(route('driver.dashboard'))->assertForbidden();
        $this->actingAs($driver)->get(route('shipper.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('shipper.dashboard'))->assertForbidden();
    }

    public function test_signed_in_user_visiting_login_is_sent_to_their_panel(): void
    {
        $this->actingAs(User::factory()->shipper()->create())
            ->get(route('login'))
            ->assertRedirect(route('shipper.dashboard'));
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->shipper()->create())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
