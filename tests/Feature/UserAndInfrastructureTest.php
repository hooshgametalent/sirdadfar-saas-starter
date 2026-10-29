<?php

namespace Tests\Feature;

use App\Contracts\SmsSender;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Order;
use App\Models\Page;
use App\Models\User;
use App\Services\Sms\LogSmsSender;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserAndInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_staff_and_editors_can_access_the_admin_panel(): void
    {
        $panel = $this->createStub(Panel::class);

        $this->assertTrue(User::factory()->admin()->create()->canAccessPanel($panel));
        $this->assertTrue(User::factory()->operator()->create()->canAccessPanel($panel));
        $this->assertTrue(User::factory()->contentEditor()->create()->canAccessPanel($panel));
        $this->assertFalse(User::factory()->shipper()->create()->canAccessPanel($panel));
        $this->assertFalse(User::factory()->driver()->create()->canAccessPanel($panel));
        $this->assertFalse(User::factory()->admin()->inactive()->create()->canAccessPanel($panel));
    }

    public function test_role_helpers(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Shipper, $user->role);
        $this->assertTrue($user->isShipper());
        $this->assertTrue($user->hasRole(UserRole::Shipper, UserRole::Driver));
        $this->assertFalse($user->hasRole(UserRole::Admin));
        $this->assertFalse($user->isStaff());
        $this->assertSame(11, strlen($user->mobile));
        $this->assertSame('صاحب بار', UserRole::Shipper->label());
    }

    public function test_order_references_are_sequential_and_unique(): void
    {
        $first = Order::factory()->create();
        $second = Order::factory()->create();

        $this->assertSame(sprintf('BAR-%06d', $first->id), $first->reference);
        $this->assertSame(sprintf('BAR-%06d', $second->id), $second->reference);
        $this->assertSame($first->reference, Order::query()->find($first->id)->reference);
        $this->assertNotSame($first->reference, $second->reference);
    }

    public function test_published_scopes_hide_drafts_and_future_articles(): void
    {
        $published = Article::factory()->published()->create();
        Article::factory()->create();
        Article::factory()->published()->create(['published_at' => now()->addWeek()]);
        $page = Page::factory()->published()->create();
        Page::factory()->create();

        $this->assertSame([$published->id], Article::query()->published()->pluck('id')->all());
        $this->assertSame([$page->id], Page::query()->published()->pluck('id')->all());
    }

    public function test_sms_sender_defaults_to_the_log_driver(): void
    {
        $sender = app(SmsSender::class);

        $this->assertInstanceOf(LogSmsSender::class, $sender);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(fn (string $message, array $context) => $context['mobile'] === '09120000001');

        $sender->send('09120000001', 'کد تأیید شما ۱۲۳۴۵ است.');
    }
}
