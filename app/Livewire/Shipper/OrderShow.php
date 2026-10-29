<?php

namespace App\Livewire\Shipper;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\FreightException;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderLifecycle;
use App\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class OrderShow extends Component
{
    public Order $order;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public string $cancelReason = '';

    public string $rating = '5';

    public string $reviewBody = '';

    public bool $publishConsent = false;

    public string $displayName = '';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);

        $this->order = $order;
        $this->displayName = app(ReviewService::class)->anonymise($this->user()->name);
    }

    public function acceptOffer(int $offerId): void
    {
        $this->authorize('view', $this->order);
        $this->resetMessages();

        $offer = $this->order->offers()->whereKey($offerId)->firstOrFail();

        try {
            $order = app(OrderLifecycle::class)->acceptOffer($offer, $this->user());

            $this->successMessage = 'پیشنهاد پذیرفته شد و سفارش '.$order->reference.' به راننده تخصیص یافت.';
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }

        $this->order->refresh();
    }

    public function cancel(): void
    {
        $this->authorize('view', $this->order);
        $this->resetMessages();
        $this->order->refresh();

        if (Gate::denies('cancel', $this->order)) {
            $this->errorMessage = 'امکان لغو این سفارش در وضعیت فعلی وجود ندارد.';

            return;
        }

        $this->validate([
            'cancelReason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            app(OrderLifecycle::class)->cancel($this->order, $this->user(), trim($this->cancelReason));

            $this->successMessage = 'سفارش لغو شد.';
            $this->reset('cancelReason');
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }

        $this->order->refresh();
    }

    public function submitReview(): void
    {
        $this->authorize('view', $this->order);
        $this->resetMessages();

        $data = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'reviewBody' => ['required', 'string', 'min:5', 'max:2000'],
            'publishConsent' => ['boolean'],
            'displayName' => ['nullable', 'string', 'max:60', 'not_regex:/[0-9۰-۹٠-٩]/u'],
        ]);

        try {
            app(ReviewService::class)->create(
                $this->user(),
                $this->order,
                (int) $data['rating'],
                $data['reviewBody'],
                (bool) $data['publishConsent'],
                filled($data['displayName']) ? trim($data['displayName']) : null,
            );

            $this->successMessage = 'نظر شما ثبت شد و پس از بررسی پشتیبانی منتشر می‌شود.';
            $this->reset('reviewBody', 'publishConsent');
        } catch (FreightException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        $order = Order::query()
            ->with([
                'originCity.province',
                'destinationCity.province',
                'vehicleType',
                'assignedDriver:id,name,mobile',
                'assignedDriver.driverProfile.vehicleType',
                'statusHistories',
                'review',
            ])
            ->findOrFail($this->order->getKey());

        $offers = $order->offers()
            ->with([
                'driver:id,name',
                'driver.driverProfile:id,user_id,vehicle_type_id',
                'driver.driverProfile.vehicleType:id,name',
            ])
            ->where(fn ($query) => $query
                ->where('status', OfferStatus::Accepted->value)
                ->orWhere(fn ($pending) => $pending->pending()->fromEligibleDrivers()))
            ->orderBy('price')
            ->get();

        $ratings = Review::query()
            ->where('status', ReviewStatus::Approved->value)
            ->whereIn('driver_id', $offers->pluck('driver_id')->all())
            ->toBase()
            ->selectRaw('driver_id, avg(rating) as average, count(*) as total')
            ->groupBy('driver_id')
            ->get()
            ->keyBy('driver_id');

        return view('livewire.shipper.order-show', [
            'details' => $order,
            'offers' => $offers,
            'ratings' => $ratings,
            'canAccept' => $order->status === OrderStatus::OpenForOffers,
            'canCancel' => Gate::allows('cancel', $order),
            'canReview' => $order->status === OrderStatus::Delivered && $order->review === null,
            'requirements' => Order::SPECIAL_REQUIREMENTS,
        ])->title('سفارش '.$order->reference);
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
