<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\FreightException;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFreightData;
use Tests\TestCase;

class ReviewServiceTest extends TestCase
{
    use CreatesFreightData, RefreshDatabase;

    public function test_shipper_can_review_a_delivered_order_once(): void
    {
        $driver = $this->approvedDriver();
        $shipper = User::factory()->shipper()->create(['name' => 'زهرا محمدی', 'mobile' => '09120000001']);
        $order = Order::factory()->delivered($driver)->create(['shipper_id' => $shipper->id]);
        $service = app(ReviewService::class);

        $review = $service->create($shipper, $order, 5, 'تحویل به موقع و راننده خوش‌قول.', true);

        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame($driver->id, $review->driver_id);
        $this->assertTrue($review->publish_consent);
        $this->assertSame('زهرا م.', $review->display_name);
        $this->assertStringNotContainsString('0912', $review->display_name);

        $this->assertThrows(
            fn () => $service->create($shipper, $order, 4, 'نظر دوم'),
            FreightException::class,
        );
        $this->assertSame(1, Review::query()->count());
    }

    public function test_review_is_rejected_before_delivery(): void
    {
        $shipper = User::factory()->shipper()->create();
        $service = app(ReviewService::class);

        foreach ([OrderStatus::OpenForOffers, OrderStatus::InTransit, OrderStatus::Cancelled] as $status) {
            $order = Order::factory()->status($status)->create(['shipper_id' => $shipper->id]);

            $this->assertThrows(fn () => $service->create($shipper, $order, 5, 'عالی'), FreightException::class);
        }

        $this->assertSame(0, Review::query()->count());
    }

    public function test_only_the_orders_shipper_can_review(): void
    {
        $driver = $this->approvedDriver();
        $order = Order::factory()->delivered($driver)->create();
        $service = app(ReviewService::class);

        $this->assertThrows(
            fn () => $service->create(User::factory()->shipper()->create(), $order, 5, 'عالی'),
            AuthorizationException::class,
        );
        $this->assertThrows(fn () => $service->create($driver, $order, 5, 'عالی'), AuthorizationException::class);
        $this->assertThrows(fn () => $service->create($order->shipper, $order, 6, 'عالی'), FreightException::class);
    }

    public function test_display_name_is_anonymised_and_never_shows_a_mobile(): void
    {
        $service = app(ReviewService::class);

        $this->assertSame('علی ر.', $service->anonymise('علی رضایی'));
        $this->assertSame('مریم', $service->anonymise('مریم'));
        $this->assertSame('مشتری بارراه', $service->anonymise('09120000001'));
        $this->assertSame('مشتری بارراه', $service->anonymise('۰۹۱۲۰۰۰۰۰۰۱'));
        $this->assertSame('مشتری بارراه', $service->anonymise(''));
    }

    public function test_publishable_scope_requires_approval_and_consent(): void
    {
        $visible = Review::factory()->approved()->create();
        Review::factory()->approved()->withoutConsent()->create();
        Review::factory()->create();
        Review::factory()->create(['status' => ReviewStatus::Rejected]);

        $this->assertSame([$visible->id], Review::query()->publishable()->pluck('id')->all());
    }

    public function test_moderation_by_content_editor_is_audited(): void
    {
        $review = Review::factory()->create();
        $editor = User::factory()->contentEditor()->create();
        $service = app(ReviewService::class);

        $service->moderate($review, $editor, ReviewStatus::Approved, 'سپاس از شما', true);

        $review->refresh();
        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertTrue($review->is_featured);
        $this->assertSame($editor->id, $review->moderated_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.approved', 'user_id' => $editor->id]);

        $this->assertThrows(
            fn () => $service->moderate($review, User::factory()->shipper()->create(), ReviewStatus::Rejected),
            AuthorizationException::class,
        );
    }
}
