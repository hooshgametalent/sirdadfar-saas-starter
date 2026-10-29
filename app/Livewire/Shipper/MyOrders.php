<?php

namespace App\Livewire\Shipper;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('سفارش‌های من')]
class MyOrders extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $status = OrderStatus::tryFrom($this->status);

        return view('livewire.shipper.my-orders', [
            'orders' => Order::query()
                ->forShipper($user)
                ->when($status, fn ($query, OrderStatus $selected) => $query->status($selected))
                ->with(['originCity', 'destinationCity', 'vehicleType'])
                ->withCount(['offers' => fn ($query) => $query->pending()])
                ->latest()
                ->paginate(10),
            'statuses' => OrderStatus::options(),
        ]);
    }
}
