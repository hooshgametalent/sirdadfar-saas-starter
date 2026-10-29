<?php

namespace Tests\Feature;

use App\Enums\DriverVerificationStatus;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\DriverProfileResource;
use App\Filament\Resources\DriverProfileResource\Pages\ViewDriverProfile;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\ReviewResource\Pages\ManageReviews;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Widgets\StatsOverview;
use App\Models\AuditLog;
use App\Models\DriverProfile;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Support\Jalali;
use App\Support\Settings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->fakeSms();

        Filament::setCurrentPanel('admin');
    }

    public function test_guests_are_sent_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('شماره موبایل');
    }

    public function test_admin_can_open_the_dashboard_and_resources(): void
    {
        $admin = User::factory()->admin()->create();
        Order::factory()->create();

        $this->actingAs($admin);

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('ui-avatars.com')
            ->assertDontSee('fonts.bunny.net')
            ->assertDontSee('fonts.googleapis.com');
        $this->get(OrderResource::getUrl('index'))->assertOk();
        $this->get(UserResource::getUrl('index'))->assertOk();
        $this->get(PaymentResource::getUrl('index'))->assertOk();
        $this->get(AuditLogResource::getUrl('index'))->assertOk();
        $this->get(ManageSettings::getUrl())->assertOk();
    }

    public function test_shippers_drivers_and_inactive_staff_get_403(): void
    {
        $this->actingAs(User::factory()->shipper()->create())->get('/admin')->assertForbidden();
        $this->actingAs($this->approvedDriver())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->admin()->inactive()->create())->get('/admin')->assertForbidden();
    }

    public function test_staff_can_sign_in_with_mobile_number_in_persian_digits(): void
    {
        $operator = User::factory()->operator()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'mobile' => Jalali::toPersianDigits($operator->mobile),
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($operator);
    }

    public function test_shippers_cannot_sign_in_to_the_admin_panel(): void
    {
        $shipper = User::factory()->shipper()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'mobile' => $shipper->mobile,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['mobile']);

        $this->assertGuest();
    }

    public function test_content_editor_sees_content_but_not_users_payments_or_orders(): void
    {
        $this->actingAs(User::factory()->contentEditor()->create());

        $this->get('/admin')->assertOk();
        $this->get(ArticleResource::getUrl('index'))->assertOk();
        $this->get(ReviewResource::getUrl('index'))->assertOk();

        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(PaymentResource::getUrl('index'))->assertForbidden();
        $this->get(OrderResource::getUrl('index'))->assertForbidden();
        $this->get(DriverProfileResource::getUrl('index'))->assertForbidden();
        $this->get(AuditLogResource::getUrl('index'))->assertForbidden();
        $this->get(ManageSettings::getUrl())->assertForbidden();
    }

    public function test_operator_manages_orders_and_drivers_but_not_users(): void
    {
        $this->actingAs(User::factory()->operator()->create());

        $this->get(OrderResource::getUrl('index'))->assertOk();
        $this->get(DriverProfileResource::getUrl('index'))->assertOk();

        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(PaymentResource::getUrl('index'))->assertForbidden();
        $this->get(ArticleResource::getUrl('index'))->assertForbidden();
    }

    public function test_order_list_shows_orders(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $orders = Order::factory()->count(3)->create();

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertCanSeeTableRecords($orders);
    }

    public function test_approve_action_goes_through_the_order_lifecycle(): void
    {
        $operator = User::factory()->operator()->create();
        $order = Order::factory()->create();
        $this->actingAs($operator);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('approve', data: ['note' => 'مدارک بار بررسی شد.'])
            ->assertNotified();

        $order->refresh();
        $this->assertSame(OrderStatus::OpenForOffers, $order->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::OpenForOffers->value,
            'changed_by' => $operator->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.open_for_offers',
            'auditable_id' => $order->id,
            'user_id' => $operator->id,
        ]);
    }

    public function test_approve_action_is_hidden_once_the_order_is_open(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $order = Order::factory()->openForOffers()->create();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionHidden('approve')
            ->assertActionVisible('cancel');
    }

    public function test_assign_offer_action_assigns_the_driver(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $order = Order::factory()->openForOffers()->create();
        $driver = $this->approvedDriver();
        $offer = Offer::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'price' => 25000000,
        ]);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('assignOffer', data: ['offer_id' => $offer->id]);

        $order->refresh();
        $this->assertSame(OrderStatus::Assigned, $order->status);
        $this->assertSame($driver->id, (int) $order->assigned_driver_id);
    }

    public function test_cancel_action_requires_a_reason_and_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->openForOffers()->create();
        $this->actingAs($admin);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('cancel', data: ['reason' => ''])
            ->assertHasFormErrors(['reason' => 'required']);

        $this->assertSame(OrderStatus::OpenForOffers, $order->refresh()->status);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('cancel', data: ['reason' => 'درخواست صاحب بار']);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.cancelled',
            'auditable_id' => $order->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_driver_approval_action_approves_the_profile(): void
    {
        $operator = User::factory()->operator()->create();
        $profile = DriverProfile::factory()->create();
        $this->actingAs($operator);

        Livewire::test(ViewDriverProfile::class, ['record' => $profile->getRouteKey()])
            ->callAction('approve');

        $profile->refresh();
        $this->assertSame(DriverVerificationStatus::Approved, $profile->verification_status);
        $this->assertSame($operator->id, (int) $profile->verified_by);
        $this->assertNotNull($profile->verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'driver.approved',
            'auditable_id' => $profile->id,
            'user_id' => $operator->id,
        ]);
    }

    public function test_driver_rejection_needs_a_reason(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $profile = DriverProfile::factory()->create();

        Livewire::test(ViewDriverProfile::class, ['record' => $profile->getRouteKey()])
            ->callAction('reject', data: ['reason' => ''])
            ->assertHasFormErrors(['reason' => 'required']);

        Livewire::test(ViewDriverProfile::class, ['record' => $profile->getRouteKey()])
            ->callAction('reject', data: ['reason' => 'تصویر کارت هوشمند ناخوانا است.']);

        $profile->refresh();
        $this->assertSame(DriverVerificationStatus::Rejected, $profile->verification_status);
        $this->assertSame('تصویر کارت هوشمند ناخوانا است.', $profile->rejection_reason);
    }

    public function test_content_editor_can_approve_and_feature_a_consented_review(): void
    {
        $editor = User::factory()->contentEditor()->create();
        $review = Review::factory()->create();
        $this->actingAs($editor);

        Livewire::test(ManageReviews::class)
            ->callAction(TestAction::make('approve')->table($review), data: [
                'admin_reply' => 'از اعتماد شما سپاسگزاریم.',
                'is_featured' => true,
            ]);

        $review->refresh();
        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertTrue($review->is_featured);
        $this->assertSame('از اعتماد شما سپاسگزاریم.', $review->admin_reply);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review.approved',
            'auditable_id' => $review->id,
            'user_id' => $editor->id,
        ]);
    }

    public function test_reviews_without_consent_are_never_featured(): void
    {
        $this->actingAs(User::factory()->contentEditor()->create());
        $review = Review::factory()->withoutConsent()->create();

        Livewire::test(ManageReviews::class)
            ->callAction(TestAction::make('approve')->table($review), data: ['is_featured' => true]);

        $review->refresh();
        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertFalse($review->is_featured);
    }

    public function test_admin_cannot_change_own_role_or_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm([
                'role' => UserRole::Shipper->value,
                'is_active' => false,
            ])
            ->call('save');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_role_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->shipper()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Operator->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Operator, $user->refresh()->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.role_changed',
            'auditable_id' => $user->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_records_a_manual_settlement(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = $this->approvedDriver();
        $this->actingAs($admin);

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'user_id' => $driver->id,
                'direction' => PaymentDirection::ToDriver->value,
                'amount' => 12500000,
                'status' => PaymentStatus::Paid->value,
                'paid_at' => now()->toDateTimeString(),
                'reference_note' => 'واریز کارت به کارت',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payment = Payment::query()->sole();
        $this->assertSame($admin->id, (int) $payment->recorded_by);
        $this->assertSame(12500000, $payment->amount);
        $this->assertSame(PaymentDirection::ToDriver, $payment->direction);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.created',
            'auditable_id' => $payment->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_settings_page_saves_values_through_the_settings_helper(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'بارراه',
                'support_phone' => '02100000000',
                'hero_text' => 'بار خود را با رانندگان تأیید شده جابه‌جا کنید.',
                'commission_percent' => 5,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('بارراه', Settings::get('site_name'));
        $this->assertSame('02100000000', Settings::get('support_phone'));
        $this->assertTrue(AuditLog::query()->where('action', 'settings.updated')->exists());
    }

    public function test_avatars_are_generated_locally(): void
    {
        $user = User::factory()->admin()->create(['name' => 'مدیر نمونه']);

        $avatar = (new InitialsAvatarProvider)->get($user);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $avatar);
        $this->assertStringContainsString('م', base64_decode(substr($avatar, strlen('data:image/svg+xml;base64,'))));
    }

    public function test_stats_widget_counts_orders(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        Order::factory()->openForOffers()->count(2)->create();
        DriverProfile::factory()->create();

        Livewire::test(StatsOverview::class)
            ->assertOk()
            ->assertSee('در انتظار پیشنهاد')
            ->assertSee('رانندگان در انتظار تأیید');
    }

    public function test_deactivating_a_driver_withdraws_pending_offers_and_hides_them_from_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $order = Order::factory()->openForOffers()->create();
        $driver = $this->approvedDriver();
        $offer = Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $driver->id]);

        Livewire::test(EditUser::class, ['record' => $driver->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($driver->refresh()->is_active);
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'offer.withdrawn_for_driver',
            'auditable_id' => $driver->id,
            'user_id' => $admin->id,
        ]);

        $offer->forceFill(['status' => OfferStatus::Pending])->save();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('assignOffer', data: ['offer_id' => $offer->id]);

        $this->assertSame(OrderStatus::OpenForOffers, $order->fresh()->status);
        $this->assertNull($order->fresh()->assigned_driver_id);
        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }
}
