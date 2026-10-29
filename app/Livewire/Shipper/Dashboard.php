<?php

namespace App\Livewire\Shipper;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('داشبورد صاحب بار')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $counts = Order::query()
            ->forShipper($user)
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $count = fn (OrderStatus ...$statuses): int => (int) collect($statuses)->sum(fn (OrderStatus $status) => $counts[$status->value] ?? 0);

        return view('livewire.shipper.dashboard', [
            'user' => $user,
            'total' => (int) $counts->sum(),
            'pendingReview' => $count(OrderStatus::PendingReview),
            'openForOffers' => $count(OrderStatus::OpenForOffers),
            'inProgress' => $count(OrderStatus::Assigned, OrderStatus::Loaded, OrderStatus::InTransit),
            'delivered' => $count(OrderStatus::Delivered),
            'recentOrders' => Order::query()
                ->forShipper($user)
                ->with(['originCity', 'destinationCity', 'vehicleType'])
                ->withCount(['offers' => fn ($query) => $query->pending()])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
