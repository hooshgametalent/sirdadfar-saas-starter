<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

class OrderPolicy
{
    use DeniesInactiveUsers;

    /**
     * Listing is scoped by query: shippers see their own orders and drivers
     * see open and assigned orders. Content editors have no access.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isShipper() || $user->isDriver();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isShipper()) {
            return $order->isOwnedBy($user);
        }

        if ($user->isDriver()) {
            return $order->status === OrderStatus::OpenForOffers || $order->isAssignedTo($user);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isShipper();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    public function approve(User $user, Order $order): bool
    {
        return $user->isStaff() && $order->status === OrderStatus::PendingReview;
    }

    public function acceptOffer(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::OpenForOffers
            && ($user->isStaff() || $order->isOwnedBy($user));
    }

    public function makeOffer(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::OpenForOffers && $user->isVerifiedDriver();
    }

    public function updateProgress(User $user, Order $order): bool
    {
        return in_array($order->status, [OrderStatus::Assigned, OrderStatus::Loaded, OrderStatus::InTransit], true)
            && ($user->isStaff() || $order->isAssignedTo($user));
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($order->status->isFinal()) {
            return false;
        }

        if ($user->isStaff()) {
            return $order->status->canTransitionTo(OrderStatus::Cancelled);
        }

        return $order->isOwnedBy($user)
            && in_array($order->status, [OrderStatus::PendingReview, OrderStatus::OpenForOffers], true);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
