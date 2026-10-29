<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Models\AuditLog;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\OfferService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class OfferServiceTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    public function test_verified_driver_can_submit_an_offer(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->openForOffers()->create();

        $offer = app(OfferService::class)->submit($driver, $order, 25000000, 'آماده حرکت');

        $this->assertSame(OfferStatus::Pending, $offer->status);
        $this->assertSame(25000000, $offer->price);
        $this->assertSame($driver->id, $offer->driver_id);
        $this->assertTrue(AuditLog::query()->where('action', 'offer.submitted')->exists());
    }

    public function test_unverified_driver_cannot_submit_an_offer(): void
    {
        $order = Order::factory()->openForOffers()->create();
        $service = app(OfferService::class);

        $this->assertThrows(fn () => $service->submit($this->pendingDriver(), $order, 25000000), FreightException::class);
        $this->assertThrows(
            fn () => $service->submit(User::factory()->driver()->create(), $order, 25000000),
            FreightException::class,
        );
        $this->assertThrows(
            fn () => $service->submit(User::factory()->shipper()->create(), $order, 25000000),
            AuthorizationException::class,
        );

        $this->assertSame(0, Offer::query()->count());
    }

    public function test_duplicate_offer_is_prevented(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->openForOffers()->create();
        $service = app(OfferService::class);

        $service->submit($driver, $order, 25000000);

        $this->assertThrows(
            fn () => $service->submit($driver, $order, 24000000),
            FreightException::class,
            'شما قبلاً برای این سفارش پیشنهاد ثبت کرده‌اید.',
        );

        $this->assertSame(1, Offer::query()->count());
        $this->assertSame(25000000, Offer::query()->first()->price);
    }

    public function test_withdrawn_offer_can_be_submitted_again(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->openForOffers()->create();
        $service = app(OfferService::class);

        $offer = $service->submit($driver, $order, 25000000);
        $service->withdraw($offer, $driver);
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);

        $again = $service->submit($driver, $order, 23000000);

        $this->assertSame($offer->id, $again->id);
        $this->assertSame(OfferStatus::Pending, $again->status);
        $this->assertSame(23000000, $again->price);
        $this->assertSame(1, Offer::query()->count());
    }

    public function test_offer_requires_order_open_for_offers_and_positive_price(): void
    {
        $driver = $this->approvedDriver();
        $service = app(OfferService::class);

        foreach ([OrderStatus::PendingReview, OrderStatus::Assigned, OrderStatus::Cancelled] as $status) {
            $order = Order::factory()->status($status)->create();
            $this->assertThrows(fn () => $service->submit($driver, $order, 25000000), FreightException::class);
        }

        $open = Order::factory()->openForOffers()->create();
        $this->assertThrows(fn () => $service->submit($driver, $open, 0), FreightException::class);
    }

    public function test_only_the_offering_driver_can_withdraw_a_pending_offer(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->openForOffers()->create();
        $service = app(OfferService::class);
        $offer = $service->submit($driver, $order, 25000000);

        $this->assertThrows(fn () => $service->withdraw($offer, $this->approvedDriver()), AuthorizationException::class);

        $service->withdraw($offer, $driver);
        $this->assertThrows(fn () => $service->withdraw($offer, $driver), FreightException::class);
    }
}
