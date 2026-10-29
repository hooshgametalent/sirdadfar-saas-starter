<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderObserver
{
    /**
     * Give every new order a temporary unique reference so the unique index
     * is satisfied before the auto increment id is known.
     */
    public function creating(Order $order): void
    {
        if (blank($order->reference)) {
            $order->reference = 'TMP-'.Str::uuid()->toString();
        }
    }

    /**
     * Replace the temporary reference with a sequential one derived from the
     * primary key, for example BAR-000001. The id is unique, so the
     * reference can never collide.
     */
    public function created(Order $order): void
    {
        if (! str_starts_with((string) $order->reference, 'TMP-')) {
            return;
        }

        $order->reference = sprintf('%s-%06d', config('freight.reference_prefix', 'BAR'), $order->getKey());
        $order->saveQuietly();
    }
}
