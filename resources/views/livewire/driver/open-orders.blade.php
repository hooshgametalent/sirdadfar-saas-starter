<div class="space-y-6">
    <x-flash :success="$successMessage" :error="$errorMessage" :from-session="false" />

    @unless ($verified)
        <section class="card" aria-labelledby="not-verified-heading">
            <h2 id="not-verified-heading" class="text-lg font-bold">حساب شما هنوز تأیید نشده است</h2>
            <p class="mt-2 text-sm text-slate-700">مشاهده بارها و ثبت پیشنهاد قیمت فقط برای رانندگان تأییدشده امکان‌پذیر است.</p>
            <a href="{{ route('driver.profile') }}" class="btn-primary mt-4">تکمیل پروفایل و مدارک</a>
        </section>
    @else
        <section class="card grid gap-4 sm:grid-cols-2" aria-label="فیلتر بارها">
            <x-form-field label="نوع خودرو" for="vehicle-filter">
                <select id="vehicle-filter" wire:model.live="vehicleTypeId" class="form-input">
                    <option value="">همه خودروها</option>
                    @foreach ($vehicleTypes as $vehicleType)
                        <option value="{{ $vehicleType->id }}" wire:key="fv-{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <x-form-field label="استان مبدأ" for="province-filter">
                <select id="province-filter" wire:model.live="originProvinceId" class="form-input">
                    <option value="">همه استان‌ها</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" wire:key="fp-{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </x-form-field>
        </section>

        <section aria-label="فهرست بارهای آماده حمل" class="space-y-4">
            @forelse ($orders as $order)
                @php($myOffer = $myOffers->get($order->id))
                <article class="card" wire:key="open-order-{{ $order->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold">
                                {{ $order->originCity?->name }} به {{ $order->destinationCity?->name }}
                                <span class="text-sm font-normal text-slate-500" dir="ltr">{{ $order->reference }}</span>
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $order->cargo_type }} · {{ \App\Support\Jalali::number($order->weight_kg) }} کیلوگرم · {{ $order->vehicleType?->name }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                بارگیری: {{ \App\Support\Jalali::date($order->pickup_date, 'l j F Y') }} ·
                                قیمت پیشنهادی صاحب بار: {{ \App\Support\Jalali::formatToman($order->offered_price) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($myOffer && $myOffer->status === \App\Enums\OfferStatus::Pending)
                                <span class="text-sm">پیشنهاد شما: {{ \App\Support\Jalali::formatToman($myOffer->price) }}</span>
                                <button type="button" class="btn-secondary" wire:click="withdrawOffer({{ $myOffer->id }})"
                                        wire:confirm="پیشنهاد شما پس گرفته شود؟" wire:loading.attr="disabled">
                                    پس گرفتن پیشنهاد
                                </button>
                            @elseif ($offerOrderId !== $order->id)
                                <button type="button" class="btn-primary" wire:click="startOffer({{ $order->id }})">ثبت پیشنهاد قیمت</button>
                            @endif
                        </div>
                    </div>

                    @if ($offerOrderId === $order->id)
                        <form wire:submit="submitOffer" class="mt-4 grid gap-4 border-t border-slate-100 pt-4 md:grid-cols-3" novalidate>
                            <x-form-field label="مبلغ پیشنهادی (تومان)" for="offer-price-{{ $order->id }}" name="offerPrice" required>
                                <input id="offer-price-{{ $order->id }}" type="text" inputmode="numeric" dir="ltr" wire:model="offerPrice" class="form-input" required
                                       @error('offerPrice') aria-invalid="true" aria-describedby="offer-price-{{ $order->id }}-error" @enderror>
                            </x-form-field>
                            <x-form-field label="توضیح برای صاحب بار" for="offer-message-{{ $order->id }}" name="offerMessage">
                                <input id="offer-message-{{ $order->id }}" type="text" wire:model="offerMessage" class="form-input"
                                       @error('offerMessage') aria-invalid="true" aria-describedby="offer-message-{{ $order->id }}-error" @enderror>
                            </x-form-field>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submitOffer">ارسال پیشنهاد</button>
                                <button type="button" class="btn-secondary" wire:click="cancelOffer">انصراف</button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <p class="card text-sm text-slate-600">در حال حاضر باری با این شرایط برای پیشنهاد قیمت وجود ندارد.</p>
            @endforelse

            @if ($orders)
                {{ $orders->links() }}
            @endif
        </section>
    @endunless
</div>
