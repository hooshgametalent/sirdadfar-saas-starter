<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * The only place where an order changes status. Every transition runs in a
 * database transaction with the order row locked, is validated against
 * OrderStatus::canTransitionTo() and writes a status history row and an
 * audit log entry.
 */
class OrderLifecycle
{
    public function __construct(
        protected AuditLogger $audit,
        protected SmsSender $sms,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(User $shipper, array $data): Order
    {
        if (! $shipper->is_active || ! $shipper->isShipper()) {
            throw new AuthorizationException('فقط صاحبان بار می‌توانند سفارش ثبت کنند.');
        }

        return DB::transaction(function () use ($shipper, $data) {
            $order = new Order;
            $order->fill(Arr::only($data, $order->getFillable()));
            $order->shipper_id = $shipper->getKey();
            $order->status = OrderStatus::PendingReview;
            $order->save();

            $this->record($order, null, OrderStatus::PendingReview, $shipper, null);

            return $order;
        });
    }

    public function approve(Order $order, User $operator, ?string $note = null): Order
    {
        $this->ensureStaff($operator);

        $order = $this->transition($order, OrderStatus::OpenForOffers, $operator, $note);

        $this->notify($order->shipper, 'سفارش '.$order->reference.' تأیید شد و آماده دریافت پیشنهاد رانندگان است.');

        return $order;
    }

    public function acceptOffer(Offer $offer, User $actor): Order
    {
        $order = DB::transaction(function () use ($offer, $actor) {
            $order = $this->lock($offer->order_id);

            if (! $actor->is_active || (! $order->isOwnedBy($actor) && ! $actor->isStaff())) {
                throw new AuthorizationException('شما اجازه پذیرش پیشنهاد برای این سفارش را ندارید.');
            }

            $from = $order->status;

            if (! $from->canTransitionTo(OrderStatus::Assigned)) {
                throw InvalidOrderTransition::between($from, OrderStatus::Assigned);
            }

            $offer = Offer::query()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();

            if ($offer->status !== OfferStatus::Pending) {
                throw FreightException::because('این پیشنهاد دیگر قابل پذیرش نیست.');
            }

            // The driver may have been deactivated or rejected after the
            // offer was submitted, so check their current state under lock.
            $driver = User::query()->with('driverProfile')->whereKey($offer->driver_id)->lockForUpdate()->first();

            if ($driver === null || ! $driver->is_active || ! $driver->isVerifiedDriver()) {
                throw FreightException::because('راننده این پیشنهاد در حال حاضر فعال یا تأیید شده نیست و پیشنهاد قابل پذیرش نیست.');
            }

            $offer->status = OfferStatus::Accepted;
            $offer->save();

            Offer::query()
                ->where('order_id', $order->getKey())
                ->whereKeyNot($offer->getKey())
                ->where('status', OfferStatus::Pending->value)
                ->update(['status' => OfferStatus::Rejected->value, 'updated_at' => now()]);

            $order->forceFill([
                'status' => OrderStatus::Assigned,
                'assigned_driver_id' => $offer->driver_id,
                'accepted_offer_id' => $offer->getKey(),
                'final_price' => $offer->price,
            ])->save();

            $this->record($order, $from, OrderStatus::Assigned, $actor, null, [
                'offer_id' => $offer->getKey(),
                'driver_id' => $offer->driver_id,
                'final_price' => $offer->price,
            ]);

            return $order;
        });

        $this->notify($order->assignedDriver, 'پیشنهاد شما برای سفارش '.$order->reference.' پذیرفته شد.');

        return $order;
    }

    public function markLoaded(Order $order, User $actor, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::Loaded, $actor, $note, guard: $this->assignedDriverOrStaff($actor));
    }

    public function markInTransit(Order $order, User $actor, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::InTransit, $actor, $note, guard: $this->assignedDriverOrStaff($actor));
    }

    public function markDelivered(Order $order, User $actor, ?string $note = null): Order
    {
        $order = $this->transition($order, OrderStatus::Delivered, $actor, $note, guard: $this->assignedDriverOrStaff($actor));

        $this->notify($order->shipper, 'سفارش '.$order->reference.' تحویل شد. لطفاً تجربه خود را ثبت کنید.');

        return $order;
    }

    public function cancel(Order $order, User $actor, string $reason): Order
    {
        if (trim($reason) === '') {
            throw FreightException::because('ثبت دلیل لغو سفارش الزامی است.');
        }

        $guard = function (Order $locked) use ($actor): void {
            if ($actor->isStaff()) {
                return;
            }

            $shipperMayCancel = $locked->isOwnedBy($actor)
                && in_array($locked->status, [OrderStatus::PendingReview, OrderStatus::OpenForOffers], true);

            if (! $shipperMayCancel) {
                throw new AuthorizationException('شما اجازه لغو این سفارش را ندارید.');
            }
        };

        $after = function (Order $locked): void {
            Offer::query()
                ->where('order_id', $locked->getKey())
                ->whereIn('status', [OfferStatus::Pending->value, OfferStatus::Accepted->value])
                ->update(['status' => OfferStatus::Rejected->value, 'updated_at' => now()]);
        };

        return $this->transition(
            $order,
            OrderStatus::Cancelled,
            $actor,
            $reason,
            ['cancelled_reason' => $reason],
            $guard,
            $after,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function transition(
        Order $order,
        OrderStatus $to,
        User $actor,
        ?string $note = null,
        array $attributes = [],
        ?Closure $guard = null,
        ?Closure $after = null,
    ): Order {
        $locked = DB::transaction(function () use ($order, $to, $actor, $note, $attributes, $guard, $after) {
            $locked = $this->lock($order->getKey());

            if ($guard !== null) {
                $guard($locked);
            }

            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InvalidOrderTransition::between($from, $to);
            }

            $locked->forceFill(array_merge($attributes, ['status' => $to]))->save();

            if ($after !== null) {
                $after($locked);
            }

            $this->record($locked, $from, $to, $actor, $note);

            return $locked;
        });

        return $this->refreshed($order, $locked);
    }

    protected function lock(int|string $orderId): Order
    {
        return Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function record(Order $order, ?OrderStatus $from, OrderStatus $to, User $actor, ?string $note, array $properties = []): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor->getKey(),
            'note' => $note,
        ]);

        $this->audit->log('order.'.$to->value, $order, array_merge([
            'reference' => $order->reference,
            'from' => $from?->value,
            'to' => $to->value,
        ], $properties), $actor);
    }

    protected function assignedDriverOrStaff(User $actor): Closure
    {
        return function (Order $locked) use ($actor): void {
            if (! $actor->isStaff() && ! $locked->isAssignedTo($actor)) {
                throw new AuthorizationException('فقط راننده تخصیص یافته یا اپراتور می‌تواند وضعیت حمل را تغییر دهد.');
            }
        };
    }

    protected function ensureStaff(User $actor): void
    {
        if (! $actor->is_active || ! $actor->isStaff()) {
            throw new AuthorizationException('فقط اپراتور یا مدیر می‌تواند این کار را انجام دهد.');
        }
    }

    /**
     * Copy the committed state into the instance the caller passed in, so
     * callers holding that instance see the new status.
     */
    protected function refreshed(Order $original, Order $fresh): Order
    {
        $original->setRawAttributes($fresh->getAttributes(), true);
        $original->unsetRelations();

        return $original;
    }

    protected function notify(?User $user, string $message): void
    {
        if ($user === null) {
            return;
        }

        $this->sms->send($user->mobile, $message);
    }
}
