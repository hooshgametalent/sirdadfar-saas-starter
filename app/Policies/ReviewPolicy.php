<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Policies\Concerns\DeniesInactiveUsers;

class ReviewPolicy
{
    use DeniesInactiveUsers;

    public function viewAny(User $user): bool
    {
        return $user->canModerateReviews();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->canModerateReviews()
            || (int) $review->shipper_id === (int) $user->getKey()
            || (int) $review->driver_id === (int) $user->getKey();
    }

    /**
     * Whether the user may write reviews at all. ReviewService checks the
     * order ownership, the delivered status and uniqueness.
     */
    public function create(User $user): bool
    {
        return $user->isShipper();
    }

    /**
     * Moderation: approve, reject, reply and feature.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->canModerateReviews();
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
