<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    public function test_shippers_only_see_their_own_orders(): void
    {
        $shipper = User::factory()->shipper()->create();
        $own = Order::factory()->create(['shipper_id' => $shipper->id]);
        $other = Order::factory()->openForOffers()->create();

        $this->assertTrue($shipper->can('view', $own));
        $this->assertFalse($shipper->can('view', $other));
        $this->assertTrue($shipper->can('create', Order::class));
        $this->assertTrue($shipper->can('cancel', $own));
        $this->assertFalse($shipper->can('update', $own));
        $this->assertFalse($shipper->can('approve', $own));
    }

    public function test_drivers_see_open_orders_and_orders_assigned_to_them(): void
    {
        $driver = $this->approvedDriver();
        $open = Order::factory()->openForOffers()->create();
        $pending = Order::factory()->create();
        $assigned = Order::factory()->assignedTo($driver)->create();
        $assignedToOther = Order::factory()->assignedTo($this->approvedDriver())->create();

        $this->assertTrue($driver->can('view', $open));
        $this->assertFalse($driver->can('view', $pending));
        $this->assertTrue($driver->can('view', $assigned));
        $this->assertFalse($driver->can('view', $assignedToOther));

        $this->assertTrue($driver->can('makeOffer', $open));
        $this->assertFalse($this->pendingDriver()->can('makeOffer', $open));
        $this->assertTrue($driver->can('updateProgress', $assigned));
        $this->assertFalse($driver->can('updateProgress', $assignedToOther));
        $this->assertFalse($driver->can('create', Order::class));
        $this->assertFalse($driver->can('cancel', $assigned));
    }

    public function test_operators_manage_orders_but_not_admin_only_resources(): void
    {
        $operator = User::factory()->operator()->create();
        $order = Order::factory()->create();

        $this->assertTrue($operator->can('viewAny', Order::class));
        $this->assertTrue($operator->can('view', $order));
        $this->assertTrue($operator->can('update', $order));
        $this->assertTrue($operator->can('approve', $order));
        $this->assertTrue($operator->can('cancel', $order));
        $this->assertTrue($operator->can('viewAny', DriverProfile::class));
        $this->assertTrue($operator->can('update', Review::factory()->create()));

        $this->assertFalse($operator->can('delete', $order));
        $this->assertFalse($operator->can('viewAny', User::class));
        $this->assertFalse($operator->can('viewAny', Payment::class));
        $this->assertFalse($operator->can('viewAny', VehicleType::class));
        $this->assertFalse($operator->can('viewAny', Setting::class));
        $this->assertFalse($operator->can('viewAny', AuditLog::class));
        $this->assertFalse($operator->can('create', Article::class));
    }

    public function test_content_editors_manage_content_and_reviews_only(): void
    {
        $editor = User::factory()->contentEditor()->create();
        $article = Article::factory()->create();

        $this->assertTrue($editor->can('viewAny', Article::class));
        $this->assertTrue($editor->can('update', $article));
        $this->assertTrue($editor->can('create', Page::class));
        $this->assertTrue($editor->can('create', Faq::class));
        $this->assertTrue($editor->can('update', Review::factory()->create()));

        $this->assertFalse($editor->can('viewAny', Order::class));
        $this->assertFalse($editor->can('view', Order::factory()->create()));
        $this->assertFalse($editor->can('viewAny', Offer::class));
        $this->assertFalse($editor->can('viewAny', DriverDocument::class));
        $this->assertFalse($editor->can('viewAny', Payment::class));
        $this->assertFalse($editor->can('viewAny', User::class));
    }

    public function test_admins_manage_users_payments_settings_and_vehicle_types(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', Payment::class));
        $this->assertTrue($admin->can('update', VehicleType::factory()->create()));
        $this->assertTrue($admin->can('viewAny', Setting::class));
        $this->assertTrue($admin->can('viewAny', AuditLog::class));
        $this->assertTrue($admin->can('update', Article::factory()->create()));
        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_inactive_accounts_are_denied_everything(): void
    {
        $admin = User::factory()->admin()->inactive()->create();
        $shipper = User::factory()->shipper()->inactive()->create();
        $order = Order::factory()->create(['shipper_id' => $shipper->id]);

        $this->assertFalse($admin->can('viewAny', User::class));
        $this->assertFalse($admin->can('view', $order));
        $this->assertFalse($shipper->can('view', $order));
    }

    public function test_driver_documents_are_private_to_owner_and_staff(): void
    {
        $profile = DriverProfile::factory()->create();
        $document = DriverDocument::create([
            'driver_profile_id' => $profile->id,
            'type' => 'national_card',
            'path' => 'driver-documents/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->assertTrue($profile->user->can('view', $document));
        $this->assertTrue($profile->user->can('delete', $document));
        $this->assertTrue(User::factory()->operator()->create()->can('view', $document));
        $this->assertFalse($this->approvedDriver()->can('view', $document));
        $this->assertFalse(User::factory()->shipper()->create()->can('view', $document));
        $this->assertFalse(User::factory()->contentEditor()->create()->can('view', $document));
    }

    public function test_offers_are_visible_to_their_driver_the_orders_shipper_and_staff(): void
    {
        $offer = Offer::factory()->create();

        $this->assertTrue($offer->driver->can('view', $offer));
        $this->assertTrue($offer->driver->can('withdraw', $offer));
        $this->assertTrue($offer->order->shipper->can('view', $offer));
        $this->assertTrue(User::factory()->operator()->create()->can('view', $offer));
        $this->assertFalse(User::factory()->shipper()->create()->can('view', $offer));
        $this->assertFalse($this->approvedDriver()->can('withdraw', $offer));
    }
}
