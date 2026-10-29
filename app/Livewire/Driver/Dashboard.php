<?php

namespace App\Livewire\Driver;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('داشبورد راننده')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = $user->driverProfile()->with('vehicleType')->first();

        return view('livewire.driver.dashboard', [
            'user' => $user,
            'profile' => $profile,
            'isVerified' => $profile?->isApproved() === true,
            'openOrders' => $profile?->isApproved() === true ? Order::query()->openForOffers()->count() : 0,
            'pendingOffers' => $user->offers()->where('status', OfferStatus::Pending->value)->count(),
            'activeJobs' => Order::query()
                ->assignedTo($user)
                ->whereIn('status', [OrderStatus::Assigned->value, OrderStatus::Loaded->value, OrderStatus::InTransit->value])
                ->count(),
            'deliveredJobs' => Order::query()->assignedTo($user)->status(OrderStatus::Delivered)->count(),
        ]);
    }
}
