<?php

namespace App\Policies;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

class OfferPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isDriver();
    }

    public function view(User $user, Offer $offer): bool
    {
        return $user->isStaff()
            || (int) $offer->driver_id === (int) $user->getKey()
            || $offer->order?->isOwnedBy($user) === true;
    }

    public function create(User $user): bool
    {
        return $user->isVerifiedDriver();
    }

    public function update(User $user, Offer $offer): bool
    {
        return false;
    }

    public function withdraw(User $user, Offer $offer): bool
    {
        return $offer->status === OfferStatus::Pending
            && (int) $offer->driver_id === (int) $user->getKey();
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }
}
