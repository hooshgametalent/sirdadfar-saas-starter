<?php

namespace App\Livewire\Driver;

use App\Exceptions\FreightException;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Orders assigned to the signed in driver. Shipper contact details are
 * shown here because the order is assigned to this driver.
 */
#[Layout('layouts.panel')]
#[Title('سفرهای من')]
class MyJobs extends Component
{
    use WithPagination;

    public const STEPS = ['loaded', 'in_transit', 'delivered'];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function advance(int $orderId, string $step): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        abort_unless(in_array($step, self::STEPS, true), 422);

        $user = $this->user();
        $order = Order::query()->findOrFail($orderId);

        abort_unless($order->isAssignedTo($user), 403, 'این سفارش به شما تخصیص داده نشده است.');

        $lifecycle = app(OrderLifecycle::class);

        try {
            $order = match ($step) {
                'loaded' => $lifecycle->markLoaded($order, $user),
                'in_transit' => $lifecycle->markInTransit($order, $user),
                'delivered' => $lifecycle->markDelivered($order, $user),
            };

            $this->successMessage = 'وضعیت سفارش '.$order->reference.' به «'.$order->status->label().'» تغییر کرد.';
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.driver.my-jobs', [
            'orders' => Order::query()
                ->assignedTo($this->user())
                ->with(['originCity.province', 'destinationCity.province', 'vehicleType', 'shipper:id,name,mobile'])
                ->latest('updated_at')
                ->paginate(10),
        ]);
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
