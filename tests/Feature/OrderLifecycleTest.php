<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Exceptions\InvalidOrderTransition;
use App\Models\AuditLog;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\OfferService;
use App\Services\OrderLifecycle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    public function test_full_happy_path_records_history_and_audit_logs(): void
    {
        $sms = $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $shipper = User::factory()->shipper()->create();
        $operator = User::factory()->operator()->create();
        $driver = $this->approvedDriver();

        $order = $lifecycle->submit($shipper, $this->orderData());

        $this->assertSame(OrderStatus::PendingReview, $order->status);
        $this->assertSame(sprintf('BAR-%06d', $order->id), $order->reference);
        $this->assertSame(['loading_help' => true], $order->fresh()->special_requirements);

        $lifecycle->approve($order, $operator);
        $this->assertSame(OrderStatus::OpenForOffers, $order->status);

        $offer = app(OfferService::class)->submit($driver, $order, 27000000, 'آماده بارگیری');
        $order = $lifecycle->acceptOffer($offer, $shipper);

        $this->assertSame(OrderStatus::Assigned, $order->status);
        $this->assertSame($driver->id, $order->assigned_driver_id);
        $this->assertSame(27000000, $order->final_price);
        $this->assertSame($offer->id, $order->accepted_offer_id);
        $this->assertSame(OfferStatus::Accepted, $offer->fresh()->status);

        $lifecycle->markLoaded($order, $driver);
        $lifecycle->markInTransit($order, $driver);
        $lifecycle->markDelivered($order, $driver);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);

        $this->assertSame(
            ['pending_review', 'open_for_offers', 'assigned', 'loaded', 'in_transit', 'delivered'],
            $order->statusHistories->map(fn (OrderStatusHistory $history) => $history->to_status->value)->all(),
        );
        $this->assertNull($order->statusHistories->first()->from_status);
        $this->assertSame($driver->id, $order->statusHistories->last()->changed_by);

        $actions = AuditLog::query()
            ->where('auditable_type', $order->getMorphClass())
            ->where('auditable_id', $order->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertSame([
            'order.pending_review',
            'order.open_for_offers',
            'order.assigned',
            'order.loaded',
            'order.in_transit',
            'order.delivered',
        ], $actions);

        $this->assertCount(1, $sms->messagesTo($driver->mobile));
        $this->assertCount(2, $sms->messagesTo($shipper->mobile));
    }

    public function test_invalid_transition_throws_and_records_nothing(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $operator = User::factory()->operator()->create();
        $order = $lifecycle->submit(User::factory()->shipper()->create(), $this->orderData());

        $this->assertThrows(fn () => $lifecycle->markLoaded($order, $operator), InvalidOrderTransition::class);
        $this->assertThrows(fn () => $lifecycle->markDelivered($order, $operator), InvalidOrderTransition::class);

        $lifecycle->approve($order, $operator);
        $this->assertThrows(fn () => $lifecycle->approve($order, $operator), InvalidOrderTransition::class);

        $this->assertSame(OrderStatus::OpenForOffers, $order->fresh()->status);
        $this->assertSame(2, OrderStatusHistory::query()->where('order_id', $order->id)->count());
    }

    public function test_invalid_transition_message_is_persian(): void
    {
        $exception = InvalidOrderTransition::between(OrderStatus::Delivered, OrderStatus::Assigned);

        $this->assertStringContainsString('تحویل شده', $exception->getMessage());
        $this->assertStringContainsString('مجاز نیست', $exception->getMessage());
        $this->assertInstanceOf(FreightException::class, $exception);
    }

    public function test_only_shippers_can_submit_and_only_staff_can_approve(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $driver = $this->approvedDriver();

        $this->assertThrows(fn () => $lifecycle->submit($driver, $this->orderData()), AuthorizationException::class);

        $order = Order::factory()->create();
        $this->assertThrows(fn () => $lifecycle->approve($order, $order->shipper), AuthorizationException::class);
        $this->assertThrows(
            fn () => $lifecycle->approve($order, User::factory()->contentEditor()->create()),
            AuthorizationException::class,
        );
    }

    public function test_accepting_an_offer_rejects_the_other_pending_offers(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $offers = app(OfferService::class);
        $order = Order::factory()->openForOffers()->create();

        $first = $offers->submit($this->approvedDriver(), $order, 25000000);
        $second = $offers->submit($this->approvedDriver(), $order, 26000000);
        $third = $offers->submit($this->approvedDriver(), $order, 27000000);
        $offers->withdraw($third, $third->driver);

        $lifecycle->acceptOffer($second, $order->shipper);

        $this->assertSame(OfferStatus::Rejected, $first->fresh()->status);
        $this->assertSame(OfferStatus::Accepted, $second->fresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $third->fresh()->status);
        $this->assertSame($second->driver_id, $order->fresh()->assigned_driver_id);
    }

    public function test_accepting_an_offer_fails_when_order_is_not_open(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $operator = User::factory()->operator()->create();
        $order = Order::factory()->create();
        $offer = Offer::factory()->create(['order_id' => $order->id, 'driver_id' => $this->approvedDriver()->id]);

        $this->assertThrows(fn () => $lifecycle->acceptOffer($offer, $operator), InvalidOrderTransition::class);

        $this->assertSame(OrderStatus::PendingReview, $order->fresh()->status);
        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
        $this->assertNull($order->fresh()->assigned_driver_id);
    }

    public function test_another_shipper_cannot_accept_an_offer(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $order = Order::factory()->openForOffers()->create();
        $offer = app(OfferService::class)->submit($this->approvedDriver(), $order, 25000000);

        $this->assertThrows(
            fn () => $lifecycle->acceptOffer($offer, User::factory()->shipper()->create()),
            AuthorizationException::class,
        );

        $this->assertSame(OrderStatus::OpenForOffers, $order->fresh()->status);
    }

    public function test_only_the_assigned_driver_or_staff_can_update_progress(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $driver = $this->approvedDriver();
        $order = Order::factory()->assignedTo($driver)->create();

        $this->assertThrows(fn () => $lifecycle->markLoaded($order, $this->approvedDriver()), AuthorizationException::class);
        $this->assertThrows(fn () => $lifecycle->markLoaded($order, $order->shipper), AuthorizationException::class);

        $lifecycle->markLoaded($order, $driver);
        $lifecycle->markInTransit($order, User::factory()->operator()->create());

        $this->assertSame(OrderStatus::InTransit, $order->fresh()->status);
    }

    public function test_shipper_can_cancel_before_assignment_only(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $shipper = User::factory()->shipper()->create();
        $order = $lifecycle->submit($shipper, $this->orderData());

        $lifecycle->cancel($order, $shipper, 'تغییر برنامه');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('تغییر برنامه', $order->fresh()->cancelled_reason);
        $this->assertTrue(AuditLog::query()->where('action', 'order.cancelled')->exists());

        $assigned = Order::factory()->assignedTo($this->approvedDriver())->create(['shipper_id' => $shipper->id]);
        $this->assertThrows(fn () => $lifecycle->cancel($assigned, $shipper, 'دلیل'), AuthorizationException::class);
        $this->assertThrows(fn () => $lifecycle->cancel($assigned, $shipper, ' '), FreightException::class);

        $lifecycle->cancel($assigned, User::factory()->admin()->create(), 'لغو توسط پشتیبانی');
        $this->assertSame(OrderStatus::Cancelled, $assigned->fresh()->status);

        $this->assertThrows(
            fn () => $lifecycle->cancel($assigned, User::factory()->admin()->create(), 'دوباره'),
            InvalidOrderTransition::class,
        );
    }

    public function test_cancelling_rejects_pending_offers(): void
    {
        $this->fakeSms();
        $lifecycle = app(OrderLifecycle::class);
        $order = Order::factory()->openForOffers()->create();
        $offer = app(OfferService::class)->submit($this->approvedDriver(), $order, 25000000);

        $lifecycle->cancel($order, User::factory()->operator()->create(), 'بار منتفی شد');

        $this->assertSame(OfferStatus::Rejected, $offer->fresh()->status);
    }
}
