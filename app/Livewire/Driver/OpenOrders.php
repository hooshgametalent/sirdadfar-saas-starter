<?php

namespace App\Livewire\Driver;

use App\Exceptions\FreightException;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\OfferService;
use App\Support\Jalali;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Orders open for offers. Only approved drivers see the list and may offer.
 * Shipper identity, mobile and street addresses are never shown here.
 */
#[Layout('layouts.panel')]
#[Title('بارهای آماده حمل')]
class OpenOrders extends Component
{
    use WithPagination;

    #[Url(as: 'vehicle')]
    public string $vehicleTypeId = '';

    #[Url(as: 'province')]
    public string $originProvinceId = '';

    public ?int $offerOrderId = null;

    public string $offerPrice = '';

    public string $offerMessage = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function updatedVehicleTypeId(): void
    {
        $this->resetPage();
    }

    public function updatedOriginProvinceId(): void
    {
        $this->resetPage();
    }

    public function startOffer(int $orderId): void
    {
        $this->authorize('create', Offer::class);

        $order = Order::query()->openForOffers()->findOrFail($orderId);

        $this->resetMessages();
        $this->resetValidation();
        $this->offerOrderId = $order->getKey();
        $this->offerPrice = (string) $order->offered_price;
        $this->offerMessage = '';
    }

    public function cancelOffer(): void
    {
        $this->reset('offerOrderId', 'offerPrice', 'offerMessage');
        $this->resetValidation();
    }

    public function submitOffer(): void
    {
        $this->authorize('create', Offer::class);
        $this->resetMessages();

        $this->offerPrice = preg_replace('/[^\d]/', '', Jalali::toLatinDigits($this->offerPrice)) ?? '';

        $data = $this->validate([
            'offerOrderId' => ['required', 'integer'],
            'offerPrice' => ['required', 'integer', 'min:100000', 'max:100000000000'],
            'offerMessage' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::query()->findOrFail($data['offerOrderId']);

        try {
            app(OfferService::class)->submit(
                $this->user(),
                $order,
                (int) $data['offerPrice'],
                filled($data['offerMessage']) ? trim($data['offerMessage']) : null,
            );

            $this->successMessage = 'پیشنهاد شما برای سفارش '.$order->reference.' ثبت شد.';
            $this->reset('offerOrderId', 'offerPrice', 'offerMessage');
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function withdrawOffer(int $offerId): void
    {
        $this->resetMessages();

        $offer = Offer::query()->where('driver_id', $this->user()->getKey())->findOrFail($offerId);

        try {
            app(OfferService::class)->withdraw($offer, $this->user());

            $this->successMessage = 'پیشنهاد شما پس گرفته شد.';
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        $user = $this->user();
        $verified = $user->isVerifiedDriver();

        $orders = null;
        $myOffers = collect();

        if ($verified) {
            $orders = Order::query()
                ->openForOffers()
                ->with(['originCity.province', 'destinationCity.province', 'vehicleType'])
                ->when(ctype_digit($this->vehicleTypeId), fn ($query) => $query->where('vehicle_type_id', (int) $this->vehicleTypeId))
                ->when(ctype_digit($this->originProvinceId), fn ($query) => $query->whereHas(
                    'originCity',
                    fn ($city) => $city->where('province_id', (int) $this->originProvinceId),
                ))
                ->orderBy('pickup_date')
                ->paginate(10);

            $myOffers = Offer::query()
                ->where('driver_id', $user->getKey())
                ->whereIn('order_id', $orders->getCollection()->modelKeys())
                ->get()
                ->keyBy('order_id');
        }

        return view('livewire.driver.open-orders', [
            'verified' => $verified,
            'orders' => $orders,
            'myOffers' => $myOffers,
            'vehicleTypes' => VehicleType::query()->active()->get(['id', 'name']),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function resetMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
