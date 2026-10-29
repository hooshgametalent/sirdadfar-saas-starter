<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_transition_matrix_matches_the_lifecycle(): void
    {
        $allowed = [
            'pending_review' => ['open_for_offers', 'cancelled'],
            'open_for_offers' => ['assigned', 'cancelled'],
            'assigned' => ['loaded', 'cancelled'],
            'loaded' => ['in_transit'],
            'in_transit' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $this->assertSame(
                    in_array($to->value, $allowed[$from->value], true),
                    $from->canTransitionTo($to),
                    $from->value.' -> '.$to->value,
                );
            }
        }
    }

    public function test_final_statuses(): void
    {
        $this->assertTrue(OrderStatus::Delivered->isFinal());
        $this->assertTrue(OrderStatus::Cancelled->isFinal());
        $this->assertFalse(OrderStatus::InTransit->isFinal());
    }

    public function test_every_status_has_a_persian_label(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $status->label());
        }

        $this->assertSame('در مسیر', OrderStatus::InTransit->label());
        $this->assertCount(7, OrderStatus::options());
    }
}
