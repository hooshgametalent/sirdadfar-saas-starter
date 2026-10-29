<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Shipper\CreateOrder;
use App\Livewire\Shipper\MyOrders;
use App\Livewire\Shipper\OrderShow;
use App\Models\City;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class ShipperPanelTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->fakeSms();
    }

    public function test_shipper_panel_pages_render(): void
    {
        $shipper = User::factory()->shipper()->create(['name' => 'شرکت نمونه']);
        $order = Order::factory()->create(['shipper_id' => $shipper->id]);

        $this->actingAs($shipper);

        $this->get(route('shipper.dashboard'))->assertOk()->assertSee('شرکت نمونه')->assertSee($order->reference);
        $this->get(route('shipper.orders.index'))->assertOk()->assertSee($order->reference);
        $this->get(route('shipper.orders.create'))->assertOk()->assertSee('مسیر حمل');
        $this->get(route('shipper.orders.show', $order))->assertOk()->assertSee($order->reference);
    }

    public function test_create_order_requires_valid_fields(): void
    {
        $shipper = User::factory()->shipper()->create();

        Livewire::actingAs($shipper)
            ->test(CreateOrder::class)
            ->call('submit')
            ->assertHasErrors([
                'origin_province_id' => 'required',
                'origin_city_id' => 'required',
                'destination_city_id' => 'required',
                'cargo_type' => 'required',
                'weight_kg' => 'required',
                'pickup_date' => 'required',
                'vehicle_type_id' => 'required',
                'offered_price' => 'required',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_city_must_belong_to_selected_province_and_province_change_resets_city(): void
    {
        $shipper = User::factory()->shipper()->create();
        $tehran = Province::factory()->create();
        $isfahan = Province::factory()->create();
        $isfahanCity = City::factory()->create(['province_id' => $isfahan->id]);

        Livewire::actingAs($shipper)
            ->test(CreateOrder::class)
            ->set('origin_province_id', (string) $isfahan->id)
            ->set('origin_city_id', (string) $isfahanCity->id)
            ->set('origin_province_id', (string) $tehran->id)
            ->assertSet('origin_city_id', '')
            ->set('origin_city_id', (string) $isfahanCity->id)
            ->call('submit')
            ->assertHasErrors(['origin_city_id' => 'exists']);
    }

    public function test_shipper_can_create_an_order(): void
    {
        $shipper = User::factory()->shipper()->create();
        $origin = City::factory()->create();
        $destination = City::factory()->create();
        $vehicle = VehicleType::factory()->create();

        $component = Livewire::actingAs($shipper)
            ->test(CreateOrder::class)
            ->set('origin_province_id', (string) $origin->province_id)
            ->set('origin_city_id', (string) $origin->id)
            ->set('origin_address', 'خیابان نمونه، پلاک ۱')
            ->set('destination_province_id', (string) $destination->province_id)
            ->set('destination_city_id', (string) $destination->id)
            ->set('destination_address', 'انبار نمونه')
            ->set('cargo_type', 'مصالح ساختمانی')
            ->set('weight_kg', '۸۰۰۰')
            ->set('pickup_date', now()->addDays(2)->toDateString())
            ->set('pickup_window_start', '08:00')
            ->set('pickup_window_end', '12:00')
            ->set('vehicle_type_id', (string) $vehicle->id)
            ->set('special_requirements', ['loading_help', 'insurance'])
            ->set('offered_price', '۲۸,۵۰۰,۰۰۰')
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::query()->firstOrFail();

        $component->assertRedirect(route('shipper.orders.show', $order));

        $this->assertSame(OrderStatus::PendingReview, $order->status);
        $this->assertSame($shipper->id, $order->shipper_id);
        $this->assertSame(8000, $order->weight_kg);
        $this->assertSame(28500000, $order->offered_price);
        $this->assertSame(['loading_help' => true, 'insurance' => true], $order->special_requirements);
        $this->assertSame(1, $order->statusHistories()->count());
    }

    public function test_pickup_date_cannot_be_in_the_past(): void
    {
        Livewire::actingAs(User::factory()->shipper()->create())
            ->test(CreateOrder::class)
            ->set('pickup_date', now()->subDay()->toDateString())
            ->call('submit')
            ->assertHasErrors(['pickup_date' => 'after_or_equal']);
    }

    public function test_shipper_cannot_view_another_shippers_order(): void
    {
        $order = Order::factory()->openForOffers()->create();
        $other = User::factory()->shipper()->create();

        $this->actingAs($other)->get(route('shipper.orders.show', $order))->assertForbidden();

        Livewire::actingAs($other)
            ->test(OrderShow::class, ['order' => $order])
            ->assertForbidden();
    }

    public function test_my_orders_lists_only_own_orders_and_filters_by_status(): void
    {
        $shipper = User::factory()->shipper()->create();
        $open = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);
        $delivered = Order::factory()->delivered()->create(['shipper_id' => $shipper->id]);
        $foreign = Order::factory()->openForOffers()->create();

        Livewire::actingAs($shipper)
            ->test(MyOrders::class)
            ->assertSee($open->reference)
            ->assertSee($delivered->reference)
            ->assertDontSee($foreign->reference)
            ->set('status', OrderStatus::Delivered->value)
            ->assertSee($delivered->reference)
            ->assertDontSee($open->reference);
    }

    public function test_offers_hide_driver_mobile_until_assignment_and_shipper_can_accept(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);
        $driver = $this->approvedDriver();
        $driver->forceFill(['name' => 'علی رانندگی', 'mobile' => '09005556677'])->save();
        $offer = Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $driver->id, 'price' => 27000000]);
        $other = Offer::factory()->create(['order_id' => $order->id]);

        $component = Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->assertSee('علی رانندگی')
            ->assertSee('پذیرش پیشنهاد')
            ->assertDontSee('09005556677');

        $component->call('acceptOffer', $offer->id)
            ->assertSet('errorMessage', null)
            ->assertSee('09005556677');

        $order->refresh();

        $this->assertSame(OrderStatus::Assigned, $order->status);
        $this->assertSame($driver->id, $order->assigned_driver_id);
        $this->assertSame(27000000, $order->final_price);
        $this->assertSame(OfferStatus::Rejected, $other->fresh()->status);
    }

    public function test_accepting_on_an_order_not_open_for_offers_shows_a_persian_error(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->create(['shipper_id' => $shipper->id]);
        $offer = Offer::factory()->create(['order_id' => $order->id]);

        $component = Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->call('acceptOffer', $offer->id);

        $this->assertStringContainsString('مجاز نیست', (string) $component->get('errorMessage'));
        $this->assertSame(OrderStatus::PendingReview, $order->fresh()->status);
    }

    public function test_shipper_cannot_accept_an_offer_of_another_order(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);
        $foreignOffer = Offer::factory()->create();

        Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->call('acceptOffer', $foreignOffer->id)
            ->assertNotFound();

        $this->assertSame(OfferStatus::Pending, $foreignOffer->fresh()->status);
    }

    public function test_shipper_can_cancel_before_assignment_only(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);

        Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->call('cancel')
            ->assertHasErrors(['cancelReason' => 'required'])
            ->set('cancelReason', 'تغییر برنامه حمل')
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);

        $assigned = Order::factory()->assignedTo($this->approvedDriver())->create(['shipper_id' => $shipper->id]);

        $component = Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $assigned])
            ->set('cancelReason', 'تغییر برنامه حمل')
            ->call('cancel');

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertSame(OrderStatus::Assigned, $assigned->fresh()->status);
    }

    public function test_shipper_can_review_after_delivery_with_explicit_consent(): void
    {
        $shipper = User::factory()->shipper()->create(['name' => 'زهرا محمدی']);
        $order = Order::factory()->delivered($this->approvedDriver())->create(['shipper_id' => $shipper->id]);

        Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->assertSet('displayName', 'زهرا م.')
            ->assertSet('publishConsent', false)
            ->set('displayName', '09120000001')
            ->call('submitReview')
            ->assertHasErrors(['reviewBody' => 'required', 'displayName' => 'not_regex'])
            ->set('displayName', 'زهرا م.')
            ->set('rating', '5')
            ->set('reviewBody', 'بار سالم و به موقع رسید.')
            ->set('publishConsent', true)
            ->call('submitReview')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null);

        $review = Review::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(5, $review->rating);
        $this->assertTrue($review->publish_consent);
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame('زهرا م.', $review->display_name);
    }

    public function test_review_form_is_not_available_before_delivery(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);

        $component = Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->assertDontSee('ثبت نظر درباره این حمل')
            ->set('reviewBody', 'نظر زودهنگام')
            ->call('submitReview');

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_offers_of_ineligible_drivers_are_hidden_and_cannot_be_accepted(): void
    {
        $shipper = User::factory()->shipper()->create();
        $order = Order::factory()->openForOffers()->create(['shipper_id' => $shipper->id]);
        $eligible = $this->approvedDriver();
        $eligible->forceFill(['name' => 'راننده فعال'])->save();
        $inactive = $this->approvedDriver();
        $inactive->forceFill(['name' => 'راننده غیرفعال', 'is_active' => false])->save();
        $unverified = $this->pendingDriver();
        $unverified->forceFill(['name' => 'راننده تأییدنشده'])->save();

        Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $eligible->id]);
        $inactiveOffer = Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $inactive->id]);
        Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $unverified->id]);

        $component = Livewire::actingAs($shipper)
            ->test(OrderShow::class, ['order' => $order])
            ->assertSee('راننده فعال')
            ->assertDontSee('راننده غیرفعال')
            ->assertDontSee('راننده تأییدنشده');

        $component->call('acceptOffer', $inactiveOffer->id);

        $this->assertNotNull($component->get('errorMessage'));
        $this->assertSame(OrderStatus::OpenForOffers, $order->fresh()->status);
        $this->assertSame(OfferStatus::Pending, $inactiveOffer->fresh()->status);
    }
}
