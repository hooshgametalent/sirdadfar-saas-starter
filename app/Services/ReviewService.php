<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\FreightException;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewService
{
    public function __construct(protected AuditLogger $audit)
    {
        //
    }

    public function create(User $shipper, Order $order, int $rating, string $body, bool $publishConsent = false, ?string $displayName = null): Review
    {
        if (! $order->isOwnedBy($shipper)) {
            throw new AuthorizationException('فقط صاحب بار همین سفارش می‌تواند نظر ثبت کند.');
        }

        if ($order->status !== OrderStatus::Delivered) {
            throw FreightException::because('ثبت نظر فقط پس از تحویل بار امکان‌پذیر است.');
        }

        if ($rating < 1 || $rating > 5) {
            throw FreightException::because('امتیاز باید عددی بین ۱ تا ۵ باشد.');
        }

        if (trim($body) === '') {
            throw FreightException::because('متن نظر نمی‌تواند خالی باشد.');
        }

        return DB::transaction(function () use ($shipper, $order, $rating, $body, $publishConsent, $displayName) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (Review::query()->where('order_id', $order->getKey())->exists()) {
                throw FreightException::because('برای این سفارش قبلاً نظر ثبت شده است.');
            }

            $review = Review::create([
                'order_id' => $order->getKey(),
                'shipper_id' => $shipper->getKey(),
                'driver_id' => $order->assigned_driver_id,
                'rating' => $rating,
                'body' => trim($body),
                'publish_consent' => $publishConsent,
                'display_name' => $this->anonymise(filled($displayName) ? $displayName : $shipper->name),
                'status' => ReviewStatus::Pending,
            ]);

            $this->audit->log('review.created', $review, ['order_id' => $order->getKey(), 'rating' => $rating], $shipper);

            return $review;
        });
    }

    public function moderate(Review $review, User $moderator, ReviewStatus $status, ?string $reply = null, bool $featured = false): Review
    {
        if (! $moderator->is_active || ! $moderator->canModerateReviews()) {
            throw new AuthorizationException('شما اجازه بررسی نظرات را ندارید.');
        }

        $review->forceFill([
            'status' => $status,
            'admin_reply' => $reply,
            'is_featured' => $featured && $status === ReviewStatus::Approved,
            'moderated_by' => $moderator->getKey(),
            'moderated_at' => now(),
        ])->save();

        $this->audit->log('review.'.$status->value, $review, ['featured' => $review->is_featured], $moderator);

        return $review;
    }

    /**
     * Build a public display name that never reveals the full name or the
     * mobile number, for example "زهرا م.".
     */
    public function anonymise(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '' || preg_match('/\d/u', $name) === 1) {
            return 'مشتری بارراه';
        }

        $parts = preg_split('/\s+/u', $name) ?: [$name];
        $first = Str::limit($parts[0], 30, '');

        if (count($parts) === 1) {
            return $first;
        }

        return $first.' '.mb_substr($parts[count($parts) - 1], 0, 1).'.';
    }
}
