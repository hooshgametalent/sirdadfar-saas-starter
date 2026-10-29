<?php

namespace App\Livewire\Shipper;

use App\Models\City;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\OrderLifecycle;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('ثبت درخواست حمل')]
class CreateOrder extends Component
{
    public string $origin_province_id = '';

    public string $origin_city_id = '';

    public string $origin_address = '';

    public string $destination_province_id = '';

    public string $destination_city_id = '';

    public string $destination_address = '';

    public string $cargo_type = '';

    public string $weight_kg = '';

    public string $volume_m3 = '';

    public string $pickup_date = '';

    public string $pickup_window_start = '';

    public string $pickup_window_end = '';

    public string $vehicle_type_id = '';

    /**
     * @var list<string>
     */
    public array $special_requirements = [];

    public string $offered_price = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', Order::class);
    }

    public function updatedOriginProvinceId(): void
    {
        $this->origin_city_id = '';
    }

    public function updatedDestinationProvinceId(): void
    {
        $this->destination_city_id = '';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $windowEnd = ['nullable', 'date_format:H:i'];

        if ($this->pickup_window_start !== '') {
            $windowEnd[] = 'after:pickup_window_start';
        }

        return [
            'origin_province_id' => ['required', 'integer', Rule::exists('provinces', 'id')],
            'origin_city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('province_id', (int) $this->origin_province_id)],
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_province_id' => ['required', 'integer', Rule::exists('provinces', 'id')],
            'destination_city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('province_id', (int) $this->destination_province_id)],
            'destination_address' => ['required', 'string', 'max:500'],
            'cargo_type' => ['required', 'string', 'max:150'],
            'weight_kg' => ['required', 'integer', 'min:1', 'max:100000'],
            'volume_m3' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'pickup_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'pickup_window_start' => ['nullable', 'date_format:H:i'],
            'pickup_window_end' => $windowEnd,
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'special_requirements' => ['array'],
            'special_requirements.*' => ['string', Rule::in(array_keys(Order::SPECIAL_REQUIREMENTS))],
            'offered_price' => ['required', 'integer', 'min:100000', 'max:100000000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function submit(): void
    {
        $this->authorize('create', Order::class);

        foreach (['weight_kg', 'offered_price'] as $field) {
            $this->{$field} = preg_replace('/[^\d]/', '', Jalali::toLatinDigits($this->{$field})) ?? '';
        }

        $this->volume_m3 = str_replace(['٫', '/'], '.', Jalali::toLatinDigits(trim($this->volume_m3)));

        $data = $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $order = app(OrderLifecycle::class)->submit($user, [
            'origin_city_id' => (int) $data['origin_city_id'],
            'destination_city_id' => (int) $data['destination_city_id'],
            'origin_address' => trim($data['origin_address']),
            'destination_address' => trim($data['destination_address']),
            'cargo_type' => trim($data['cargo_type']),
            'weight_kg' => (int) $data['weight_kg'],
            'volume_m3' => filled($data['volume_m3']) ? $data['volume_m3'] : null,
            'pickup_date' => $data['pickup_date'],
            'pickup_window_start' => filled($data['pickup_window_start']) ? $data['pickup_window_start'] : null,
            'pickup_window_end' => filled($data['pickup_window_end']) ? $data['pickup_window_end'] : null,
            'vehicle_type_id' => (int) $data['vehicle_type_id'],
            'special_requirements' => $data['special_requirements'] === [] ? null : array_fill_keys($data['special_requirements'], true),
            'offered_price' => (int) $data['offered_price'],
            'notes' => filled($data['notes']) ? trim($data['notes']) : null,
        ]);

        session()->flash('status', 'درخواست حمل '.$order->reference.' ثبت شد و پس از بررسی اپراتور برای رانندگان واجد شرایط ارسال می‌شود.');

        $this->redirectRoute('shipper.orders.show', ['order' => $order->getKey()]);
    }

    public function render(): View
    {
        return view('livewire.shipper.create-order', [
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'originCities' => $this->citiesOf($this->origin_province_id),
            'destinationCities' => $this->citiesOf($this->destination_province_id),
            'vehicleTypes' => VehicleType::query()->active()->get(['id', 'name', 'capacity_kg']),
            'requirements' => Order::SPECIAL_REQUIREMENTS,
            'pickupDateJalali' => $this->pickupDateJalali(),
        ]);
    }

    /**
     * @return Collection<int, City>
     */
    protected function citiesOf(string $provinceId): Collection
    {
        if (! ctype_digit($provinceId)) {
            return collect();
        }

        return City::query()->where('province_id', (int) $provinceId)->orderBy('name')->get(['id', 'name']);
    }

    protected function pickupDateJalali(): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->pickup_date) !== 1) {
            return null;
        }

        $date = rescue(fn () => Carbon::createFromFormat('!Y-m-d', $this->pickup_date), null, false);

        return $date instanceof Carbon ? Jalali::date($date, 'l j F Y') : null;
    }
}
