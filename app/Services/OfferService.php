<?php

namespace App\Services;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(protected AuditLogger $audit)
    {
        //
    }

    public function submit(User $driver, Order $order, int $price, ?string $message = null): Offer
    {
        if (! $driver->is_active || ! $driver->isDriver()) {
            throw new AuthorizationException('فقط رانندگان می‌توانند پیشنهاد قیمت ثبت کنند.');
        }

        if (! $driver->isVerifiedDriver()) {
            throw FreightException::because('حساب راننده شما هنوز تأیید نشده است و امکان ثبت پیشنهاد ندارید.');
        }

        if ($price <= 0) {
            throw FreightException::because('مبلغ پیشنهاد باید بیشتر از صفر باشد.');
        }

        return DB::transaction(function () use ($driver, $order, $price, $message) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::OpenForOffers) {
                throw FreightException::because('این سفارش در حال حاضر پیشنهاد جدید نمی‌پذیرد.');
            }

            $offer = Offer::query()
                ->where('order_id', $order->getKey())
                ->where('driver_id', $driver->getKey())
                ->lockForUpdate()
                ->first();

            if ($offer !== null && $offer->status !== OfferStatus::Withdrawn) {
                throw FreightException::because('شما قبلاً برای این سفارش پیشنهاد ثبت کرده‌اید.');
            }

            $offer ??= new Offer([
                'order_id' => $order->getKey(),
                'driver_id' => $driver->getKey(),
            ]);

            $offer->fill([
                'price' => $price,
                'message' => $message,
                'status' => OfferStatus::Pending,
            ])->save();

            $this->audit->log('offer.submitted', $offer, [
                'order_id' => $order->getKey(),
                'price' => $price,
            ], $driver);

            return $offer;
        });
    }

    public function withdraw(Offer $offer, User $driver): Offer
    {
        return DB::transaction(function () use ($offer, $driver) {
            $offer = Offer::query()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $offer->driver_id !== (int) $driver->getKey()) {
                throw new AuthorizationException('شما اجازه پس گرفتن این پیشنهاد را ندارید.');
            }

            if ($offer->status !== OfferStatus::Pending) {
                throw FreightException::because('فقط پیشنهادهای در انتظار پاسخ قابل پس گرفتن هستند.');
            }

            $offer->status = OfferStatus::Withdrawn;
            $offer->save();

            $this->audit->log('offer.withdrawn', $offer, ['order_id' => $offer->order_id], $driver);

            return $offer;
        });
    }

    /**
     * Withdraw every pending offer of a driver who is no longer eligible
     * (deactivated or verification rejected). Returns the number of offers
     * withdrawn. The driver can submit again once eligible.
     */
    public function withdrawAllPendingFor(User $driver, ?User $actor = null, string $reason = 'driver_ineligible'): int
    {
        return DB::transaction(function () use ($driver, $actor, $reason) {
            $ids = Offer::query()
                ->where('driver_id', $driver->getKey())
                ->pending()
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            Offer::query()
                ->whereKey($ids->all())
                ->update(['status' => OfferStatus::Withdrawn->value, 'updated_at' => now()]);

            $this->audit->log('offer.withdrawn_for_driver', $driver, [
                'offer_ids' => $ids->all(),
                'reason' => $reason,
            ], $actor);

            return $ids->count();
        });
    }
}
